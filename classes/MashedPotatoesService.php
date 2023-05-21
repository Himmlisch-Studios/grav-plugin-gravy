<?php

namespace Grav\Plugin\Gravy;

use Exception;
use \Grav\Framework\File\MarkdownFile;
use \Grav\Framework\File\Formatter\MarkdownFormatter;
use \Grav\Common\Utils;

class MashedPotatoesService
{
    protected array $constructor;
    protected string $folder;
    protected ?string $lang;
    protected ?string $defaultLang;

    protected const LISTS_TO_VALIDATE = [
        'added',
        'edited',
        'removed'
    ];

    protected const PUBLIC_MODULE_KEYS = [
        'x',
        'y',
        'w'
    ];

    protected const MODULE_KEYS = [
        'id',
        'name',
        'template',
        'x',
        'y',
        'w'
    ];

    public function __construct(
        mixed $constructor,
        string $folder,
        ?string $lang = 'en',
        ?string $defaultLang = 'en',
    ) {
        $this->folder = $folder;
        $this->lang = $lang;
        $this->defaultLang = $defaultLang;

        if (!$constructor) {
            return;
        }

        $constructor = is_array($constructor) ? $constructor : json_decode($constructor, true);

        try {
            $this->constructor =  $this->validateConstructor($constructor, ['added']);
        } catch (\Throwable $_) {
        }
    }

    public function edible()
    {
        return isset($this->constructor);
    }

    public function processAdded(
        $pages,
        $demoService = null
    ) {
        $modules = $this->constructor['added'];

        if (count($modules) == 0) {
            return true;
        }

        $collection = $pages->getCollection();
        $folder = $collection->get(trim($this->folder, '/'))?->folder();

        foreach ($modules as $module) {
            $template = $module['template'];

            $demo = [];
            if ($demoService) {
                $demo = ($demoService($template) ?? [])['demo'] ?? [];
                if (isset($demo['constructor'])) {
                    unset($demo['constructor']);
                }
            }

            $template = str_replace('modular/', '', $template);
            $slug = strtolower(Utils::generateRandomString(16));
            $lang = $this->lang;

            $userData = [
                'header' => $this->newHeader($module),
            ];

            $userData = array_replace_recursive($userData, $demo);
            $object = $pages->createObject(
                [
                    'folder' => "{$folder}/_{$slug}",
                    'template' => $template,
                    'lang' => $lang,
                    ...$userData
                ]
            );

            if (!$object->save()) {
                return false;
            }
        }

        return true;
    }

    public function processEdited(
        $pages,
    ) {
        $modules = $this->filterRemoved($this->constructor['edited']);

        foreach ($modules as $module) {
            $page = $pages->getCollection()->get(trim($module['id'], '/'));;

            if (!$this->editPage($page, $module)) {
                return false;
            }
        }
        return true;
    }

    public function processRemoved(
        $pages
    ) {
        foreach ($this->constructor['removed'] as $values) {
            $page = $pages->getCollection()->get(trim($values['id'], '/'));
            $page->delete();
        }

        return true;
    }

    public function filterRemoved($modules)
    {
        $keys = array_keys($this->constructor['removed']);

        return array_filter($modules, fn ($k) => !in_array($k, $keys), ARRAY_FILTER_USE_KEY);
    }

    public function cleanModule($module)
    {
        return array_filter($module, fn ($k) => in_array($k, static::PUBLIC_MODULE_KEYS), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Manual page editing since Pages 
     * haven't initialized properly
     * 
     * @return bool
     */
    protected function editPage($page, $module)
    {
        $extension = $page->extension();
        $path = $filePath = $page->filePath();

        if (isset($this->lang)) {
            $filePath = str_replace($extension, ".{$this->lang}{$extension}", $path);
        }

        // We try to get localised file
        $file = $this->getFile($filePath);

        $exists = $reallyExists = $file->exists();

        // If it doesn't directly exists, lookup the alternative langs
        if (!$exists && isset($this->defaultLang)) {
            $otherPaths = [
                str_replace($extension, ".{$this->defaultLang}{$extension}", $path),
                $path
            ];

            foreach ($otherPaths as $path) {
                if (!is_null($this->getFile($path))) {
                    $exists = true;
                    break;
                }
            }
        }

        if (!$exists) {
            return false;
        }

        if ($reallyExists) {
            $data = $file->load();
            $data['header']['constructor'] = $this->cleanModule($module);
        } else {
            $data =  ['header' => $this->newHeader($module)];
        }

        if (!$file->isWritable()) {
            return false;
        }

        $file->save($data);
        return true;
    }

    protected function newHeader($module)
    {
        return [
            'title' => $module['name'],
            'constructor' => $this->cleanModule($module)
        ];
    }

    protected function getFile($path)
    {
        $file = new MarkdownFile(
            $path,
            new MarkdownFormatter
        );

        return $file;
    }

    protected function validateConstructor($constructor, $filterBeforeInspect = [])
    {
        foreach (static::LISTS_TO_VALIDATE as $key) {
            if (!isset($constructor[$key])) {
                throw new Exception(ucfirst($key) . " modules not present");
            }

            if (!is_array($constructor[$key])) {
                throw new Exception(ucfirst($key) . " modules is not an array");
            }

            if (in_array($key, $filterBeforeInspect)) {
                $this->filterDeletedModules($constructor[$key]);
            }
        }

        foreach (static::LISTS_TO_VALIDATE as $key) {
            $constructor[$key] = $this->validateModules($constructor[$key]);
        }

        return $constructor;
    }

    protected function validateModules($modules)
    {
        return array_map(function ($module) {
            if (!is_array($module)) {
                throw new Exception("Invalid module given");
            }

            $validated = [];
            foreach (static::MODULE_KEYS as $key) {
                if (!isset($module[$key]) || is_null($module[$key])) {
                    throw new Exception("Malformed module given");
                }

                $validated[$key] = $module[$key];
            }

            return $validated;
        }, $modules);
    }

    protected function filterDeletedModules(&$modules)
    {
        $modules = array_filter($modules, fn ($v) => !is_null($v));
    }
}
