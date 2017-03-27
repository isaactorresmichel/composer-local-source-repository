# Composer Local Source Repository
The goal of this plugin is to have non-published (local) `repository` packages, inside our main project. And have them
*installed/mapped* on the same source dir, without having to rely on symbolic links, full package copies to 
secondary directories or the vendor directory.

## Requirements 
 - **composer-plugin-api**: ^1.1
 - **composer/installers**: ^1.0

## Example composer.json File
This is an example for a custom drupal-module package. The only important part to set in your *composer.json* are:
 
* Add the local `repositories` to your package.
* Set your `extra` files paths for **composer/installers** wich tells composer where to map the local packages. 
For more info look [Composer installers](https://github.com/composer/installers).
* Set the dependency `isaactorresmichel/composer-local-source-repository": "^1.0@alpha"` on your main `composer.json` and source packages (the packages to install). 

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

