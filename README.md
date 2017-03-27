# Composer Local Source Repository
This composer plugin is for PHP developers that creates custom local (non-published ) composer packages, and want to 
have their dependencies managed by composer. 

This package allows them to have their sources inside a bigger project and be managed from composer without having to rely
on symbolic links or packages copies. The goal of plugin is to have a simple package routed to a install path.
 
## Requirements 
 - **composer-plugin-api**: ^1.1
 - **composer/installers**: ^1.0

## Example composer.json File
This is an example for a custom drupal-module package. The only important part to set in your *composer.json* are:
 
* Add the local `repositories` to your package.
* Set your `extra` files paths for **composer/installers** wich tells composer to load the custom installers. 
For more info look [Composer installers](https://github.com/composer/installers)
* Set your `require` statements for your custom packages. 

```json
{
  "repositories": {
    "modules-custom-event-log-repository": {
      "type": "path",
      "url": "web/modules/custom/event-logger"
    }
  },
  "require": {
    "drupal/event-logger": "@dev"
  },
  "extra": {
    "installer-paths": {
      "web/modules/custom/{$name}": [
        "drupal/event-logger"
      ]
    }
  }
}
```

