<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Asset;

use Symfony\Component\HttpKernel\KernelInterface;

class AssetConfigCache extends \Oro\Bundle\AssetBundle\Cache\AssetConfigCache
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct($kernel);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $config['paths'] = $this->getBundlesPath();
        $config['applicationUrl'] = $this->getApplicationUrl();

        @file_put_contents($this->getFilePath($cacheDir), \json_encode($config));
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $cacheDir): bool
    {
        // Always update cache
        return false;
    }

    /**
     * @param string $cacheDir
     * @return string
     */
    private function getFilePath(string $cacheDir): string
    {
        return $cacheDir.'/asset-config.json';
    }

    /**
     * @return array
     */
    private function getBundlesPath(): array
    {
        $paths = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            $paths[] = $bundle->getPath();
        }

        return $paths;
    }

    /**
     * @return string
     */
    private function getApplicationUrl(): string
    {
        // Do not use public path https://webpack.js.org/guides/public-path/
        // Application should works without configured url
        // Because in symfony it's not required
        return '/';
    }
}
