<?php

namespace isaactorresmichel\Composer;

use Composer\Downloader\VcsCapableDownloaderInterface;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionGuesser;
use Composer\Package\Version\VersionParser;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Download a package from a local path.
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Johann Reinke <johann.reinke@gmail.com>
 */
class CustomPathDownloader extends CustomFileDownloader implements VcsCapableDownloaderInterface
{
    const STRATEGY_SYMLINK = 10;
    const STRATEGY_MIRROR = 20;

    /**
     * {@inheritdoc}
     */
    public function download(PackageInterface $package, $path, $output = true)
    {
        // If destination is source, just lock the package.
        if ($this->destinationIsSource($package, $path)) {
            $this->io->writeError(sprintf(
                'Package %s installed to source directory %s',
                $package->getName(), $path
              )
            );
            return;
        }

        $url     = $package->getDistUrl();
        $realUrl = realpath($url);
        if (false === $realUrl || !file_exists($realUrl) || !is_dir($realUrl)) {
            throw new \RuntimeException(sprintf(
              'Source path "%s" is not found for package %s', $url,
              $package->getName()
            ));
        }

        // Get the transport options with default values
        $transportOptions = $package->getTransportOptions() + array('symlink' => null);

        // When symlink transport option is null, both symlink and mirror are allowed
        $currentStrategy   = self::STRATEGY_SYMLINK;
        $allowedStrategies = array(
          self::STRATEGY_SYMLINK,
          self::STRATEGY_MIRROR
        );

        $mirrorPathRepos = getenv('COMPOSER_MIRROR_PATH_REPOS');
        if ($mirrorPathRepos) {
            $currentStrategy = self::STRATEGY_MIRROR;
        }

        if (true === $transportOptions['symlink']) {
            $currentStrategy   = self::STRATEGY_SYMLINK;
            $allowedStrategies = array(self::STRATEGY_SYMLINK);
        } elseif (false === $transportOptions['symlink']) {
            $currentStrategy   = self::STRATEGY_MIRROR;
            $allowedStrategies = array(self::STRATEGY_MIRROR);
        }

        $fileSystem = new Filesystem();
        $this->filesystem->removeDirectory($path);

        if ($output) {
            $this->io->writeError(sprintf(
              '  - Installing <info>%s</info> (<comment>%s</comment>): ',
              $package->getName(),
              $package->getFullPrettyVersion()
            ), false);
        }

        $isFallback = false;
        if (self::STRATEGY_SYMLINK == $currentStrategy) {
            try {
                if (Platform::isWindows()) {
                    // Implement symlinks as NTFS junctions on Windows
                    $this->io->writeError(sprintf('Junctioning from %s', $url),
                      false);
                    $this->filesystem->junction($realUrl, $path);
                } else {
                    $absolutePath = $path;
                    if (!$this->filesystem->isAbsolutePath($absolutePath)) {
                        $absolutePath = getcwd() . DIRECTORY_SEPARATOR . $path;
                    }
                    $shortestPath = $this->filesystem->findShortestPath($absolutePath,
                      $realUrl);
                    $path         = rtrim($path, "/");
                    $this->io->writeError(sprintf('Symlinking from %s', $url),
                      false);
                    $fileSystem->symlink($shortestPath, $path);
                }
            } catch (IOException $e) {
                if (in_array(self::STRATEGY_MIRROR, $allowedStrategies)) {
                    $this->io->writeError('');
                    $this->io->writeError('    <error>Symlink failed, fallback to use mirroring!</error>');
                    $currentStrategy = self::STRATEGY_MIRROR;
                    $isFallback      = true;
                } else {
                    throw new \RuntimeException(sprintf('Symlink from "%s" to "%s" failed!',
                      $realUrl, $path));
                }
            }
        }

        // Fallback if symlink failed or if symlink is not allowed for the package
        if (self::STRATEGY_MIRROR == $currentStrategy) {
            $this->io->writeError(sprintf('%sMirroring from %s',
              $isFallback ? '    ' : '', $url), false);
            $fileSystem->mirror($realUrl, $path);
        }

        $this->io->writeError('');
    }

    /**
     * {@inheritDoc}
     */
    public function remove(PackageInterface $package, $path, $output = true)
    {
        /**
         * For junctions don't blindly rely on Filesystem::removeDirectory as it may be overzealous. If a process
         * inadvertently locks the file the removal will fail, but it would fall back to recursive delete which
         * is disastrous within a junction. So in that case we have no other real choice but to fail hard.
         */
        if (Platform::isWindows() && $this->filesystem->isJunction($path)) {
            if ($output) {
                $this->io->writeError("  - Removing junction for <info>" . $package->getName() . "</info> (<comment>" . $package->getFullPrettyVersion() . "</comment>)");
            }
            if (!$this->filesystem->removeJunction($path)) {
                $this->io->writeError("    <warn>Could not remove junction at " . $path . " - is another process locking it?</warn>");
                throw new \RuntimeException('Could not reliably remove junction for package ' . $package->getName());
            }
        } else {
            parent::remove($package, $path, $output);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getVcsReference(PackageInterface $package, $path)
    {
        $parser  = new VersionParser;
        $guesser = new VersionGuesser($this->config,
          new ProcessExecutor($this->io), $parser);
        $dumper  = new ArrayDumper;

        $packageConfig = $dumper->dump($package);
        if ($packageVersion = $guesser->guessVersion($packageConfig, $path)) {
            return $packageVersion['commit'];
        }
    }
}
