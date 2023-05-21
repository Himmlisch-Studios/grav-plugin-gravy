<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Plugin\Gravy\MashedPotatoesService;

/**
 * Class GravyPlugin
 * @package Grav\Plugin
 */
class GravyPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        if (!$this->isAdmin()) {
            $this->enable([
                'onTwigInitialized' => ['onTwigInitialized', 0],
            ]);
            return;
        }

        $this->enable([
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
            'onTask.save' => ['onPageTaskSave', 0],
        ]);
    }

    public function onGetPageBlueprints($event): void
    {
        $types = $event->types;
        $types->scanBlueprints('plugin://gravy/blueprints');
    }

    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigInitialized($e)
    {
        $this->grav['twig']->twig()->addFilter(
            new \Twig_SimpleFilter('gravy', [$this, 'gravy'])
        );
    }

    public function onAdminTwigTemplatePaths($event): void
    {
        $paths = $event['paths'];
        $paths[] = __DIR__ . '/admin/themes/grav/templates';
        $event['paths'] = $paths;
    }

    public function gravy($collection)
    {
        $entries = [];
        foreach ($collection as $key => $object) {
            if ($object) {
                $entries[$object->route()] = $object;
            }
        }

        usort($entries, function ($a, $b) {
            $aConstructor = $a->header()?->constructor ?? [];
            $aY = $aConstructor['y'] ?? 0;
            $aX = $aConstructor['x'] ?? 0;

            $bConstructor = $b->header()?->constructor ?? [];
            $bY = $bConstructor['y'] ?? 0;
            $bX = $bConstructor['x'] ?? 0;

            $result = $aY < $bY ? -1 : ($aY > $bY ? 1 : ($aX < $bX ? -1 : 1));
            return $result;
        });

        return $entries;
    }

    public function onPageTaskSave()
    {
        if (!isset($this->grav['config']['plugins']['admin'])) {
            return;
        }

        $admin_route = ($this->grav['config']['plugins']['admin']['route'] ?? '/admin') . '/pages';
        $route = $this->grav['uri']->path();

        if (!str_starts_with($route, $admin_route)) {
            return;
        }

        $pages = $this->grav['flex']->getDirectory('pages');
        $lang = $this->grav['language'];
        $request = $this->grav['request'];

        $data = $request->getParsedBody()['data']['header']['constructor'] ?? null;
        unset($_REQUEST['data']['header']['constructor']);

        $mashedPotates = new MashedPotatoesService(
            $data,
            str_replace($admin_route, '', $route),
            $lang->getActive(),
            $lang->getDefault()
        );

        if (!$mashedPotates->edible()) {
            return;
        }

        if (!$mashedPotates->processAdded(
            $pages,
            fn ($template) => $this->grav['pages']->blueprints($template)
        )) {
            return;
        }

        if (!$mashedPotates->processEdited(
            $pages
        )) {
            return;
        }

        if (!$mashedPotates->processRemoved(
            $pages
        )) {
            return;
        }
    }
}
