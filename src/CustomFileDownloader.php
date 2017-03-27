<?php


namespace isaactorresmichel\Composer;

use Composer\Downloader\DownloaderInterface;
use Composer\Downloader\FileDownloader;
use Composer\Package\PackageInterface;

/**
 * Base downloader for files
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author François Pluchino <francois.pluchino@opendisplay.com>
 * @author Nils Adermann <naderman@naderman.de>
 */
class CustomFileDownloader extends FileDownloader
{

    /**
     * {@inheritDoc}
     */
    public function remove(PackageInterface $package, $path, $output = true)
    {
        if ($this->destinationIsSource($package, $path)) {
            if ($output) {
                $this->io->writeError("  - Removing <info>" . $package->getName() . "</info> skipped (installed from source) (<comment>" . $package->getFullPrettyVersion() . "</comment>)");
            }
            return;
        }

        parent::remove($package, $path, $output);
    }

    /**
     * Check if a package destination and source are the same.
     *
     * @param PackageInterface $package
     * @param string $path
     *
     * @return boolean
     */
    protected function destinationIsSource(PackageInterface $package, $path)
    {
        $url     = $package->getDistUrl();
        $realUrl = realpath($url);
        return strpos(realpath($path) . DIRECTORY_SEPARATOR,
          $realUrl . DIRECTORY_SEPARATOR) === 0;
    }
}
