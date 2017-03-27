<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace isaactorresmichel\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

/**
 * Override for base library installer. Code taken from David García on
 * https://github.com/composer/composer/pull/6174/files
 *
 * @author David García <https://github.com/david-garcia-garcia/>
 * @author Isaac Torres <https://github.com/isaactorresmichel>
 *
 * @see \Composer\Downloader\FileDownloader
 */
class CustomLibraryInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function uninstall(
      InstalledRepositoryInterface $repo,
      PackageInterface $package
    ) {
        if (!$repo->hasPackage($package)) {
            throw new \InvalidArgumentException('Package is not installed: ' . $package);
        }

        $this->removeCode($package);
        $this->binaryInstaller->removeBinaries($package);
        $repo->removePackage($package);
    }
}
