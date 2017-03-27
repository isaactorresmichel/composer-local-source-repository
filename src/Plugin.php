<?php
namespace isaactorresmichel\Composer;

use Composer\Cache;
use Composer\Composer;
use Composer\Downloader\DownloadManager;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\Installer\InstallationManager;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use ReflectionClass;

/**
 * Class Plugin
 * @package isaactorresmichel\Composer
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        /** @var  $installer */
        $installer = new CustomLibraryInstaller($io, $composer);
        $composer->getInstallationManager()->addInstaller($installer);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
          PackageEvents::POST_PACKAGE_INSTALL => [
            ['postPackageInstall']
          ]
        ];
    }

    /**
     * Listener to post-package-install event.
     *
     * @param \Composer\Installer\PackageEvent $event
     */
    public static function postPackageInstall(PackageEvent $event)
    {

        /**
         * Makes sure installer is first on the list.
         */
        static::setCustomInstaller($event);

        /**
         * Overrides composer downloaders.
         */
        static::setCustomDownloader($event);
    }

    /**
     * Sets custom downloaders.
     *
     * @param PackageEvent $event
     */
    private static function setCustomDownloader($event)
    {
        /** @var DownloadManager $download_manager */
        $download_manager = $event->getComposer()->getDownloadManager();

        if (($download_manager->getDownloader('path') instanceof CustomPathDownloader)
            && ($download_manager->getDownloader('file') instanceof CustomFileDownloader)
        ) {
            return;
        }

        $cache = null;
        if ($event->getComposer()->getConfig()->get('cache-files-ttl') > 0) {
            $cache = new Cache(
              $event->getIO(),
              $event->getComposer()->getConfig()->get('cache-files-dir'),
              'a-z0-9_./'
            );
        }

        $custom_path_downloader = new CustomPathDownloader(
          $event->getIO(),
          $event->getComposer()->getConfig(),
          $event->getComposer()->getEventDispatcher(),
          $cache,
          Factory::createRemoteFilesystem(
            $event->getIO(),
            $event->getComposer()->getConfig()
          )
        );

        $custom_file_downloader = new CustomFileDownloader(
          $event->getIO(),
          $event->getComposer()->getConfig(),
          $event->getComposer()->getEventDispatcher(),
          $cache,
          Factory::createRemoteFilesystem(
            $event->getIO(),
            $event->getComposer()->getConfig()
          )
        );

        $download_manager->setDownloader('path', $custom_path_downloader);
        $download_manager->setDownloader('file', $custom_file_downloader);
    }

    /**
     * Sets custom installer.
     *
     * @param PackageEvent $event
     */
    private static function setCustomInstaller($event)
    {
        /** @var InstallationManager $installation_manager */
        $installation_manager = $event->getComposer()->getInstallationManager();

        $reflector = new ReflectionClass($installation_manager);
        $installers_prop = $reflector->getProperty('installers');
        $installers_prop->setAccessible(true);
        $installers = $installers_prop->getValue($installation_manager);

        if ($installers && $installers[0] instanceof CustomLibraryInstaller) {
            return;
        }

        foreach ($installers as $index => $installer) {
            if ($installer instanceof CustomLibraryInstaller) {
                unset($installers[$index]);
            }
        }

        $installer = new CustomLibraryInstaller($event->getIO(),
          $event->getComposer());
        array_unshift($installers, $installer);

        $installers_prop->setValue($installation_manager, $installers);
        $cache_prop = $reflector->getProperty('cache');
        $cache_prop->setAccessible(true);
        $cache_prop->setValue($installation_manager, []);
    }

}