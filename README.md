# Gravy Plugin

Gravy is a website constructor Frontend for [Grav CMS](http://github.com/getgrav/grav). 

It interfaces with the native [modular system](https://learn.getgrav.org/17/content/modular#modules), to provide a friendlier experience without having to commit into a much heavier and complex framework such as [Gantry] (https://github.com/gantry/gantry5).

## Installation

Installing the Gravy plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install gravy

This will install the Gravy plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/gravy`.

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `gravy`. You can find these files on [GitHub](https://github.com/himmlisch-studios/grav-plugin-gravy) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/gravy
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/himmlisch-studios/grav-plugin-gravy/blob/master/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/gravy/gravy.yaml` to `user/config/plugins/gravy.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

Note that if you use the Admin Plugin, a file with your configuration named gravy.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

**Describe how to use the plugin.**

## Credits

**Did you incorporate third-party code? Want to thank somebody?**

## To Do

- [ ] Future plans, if any

