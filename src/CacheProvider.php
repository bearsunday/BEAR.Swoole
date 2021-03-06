<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\AppMeta\AbstractAppMeta;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\ChainCache;
use Doctrine\Common\Cache\FilesystemCache;
use Ray\Di\Di\Named;
use Ray\Di\ProviderInterface;

class CacheProvider implements ProviderInterface
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @Named("namespace=cache_namespace")
     */
    #[Named('namespace=cache_namespace')]
    public function __construct(AbstractAppMeta $appMeta, string $namespace)
    {
        $this->cacheDir = $appMeta->tmpDir . '/cache';
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $cache = new ChainCache([new ArrayCache, new FilesystemCache($this->cacheDir)]);
        $cache->setNamespace($this->namespace);

        return $cache;
    }
}
