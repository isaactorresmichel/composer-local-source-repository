<?php


namespace isaactorresmichel\Composer;

use Composer\Downloader\FileDownloader;
use Composer\Package\PackageInterface;

/**
 * Override for base file downloader. Code taken from David García on
 * https://github.com/composer/composer/pull/6174/files
 *
 * @author David García <https://github.com/david-garcia-garcia/>
 * @author Isaac Torres <https://github.com/isaactorresmichel>
 *
 * @see \Composer\Downloader\FileDownloader
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
        $url = $package->getDistUrl();
        $realUrl = realpath($url);
        return strpos(realpath($path) . DIRECTORY_SEPARATOR,
          $realUrl . DIRECTORY_SEPARATOR) === 0;
    }
}
