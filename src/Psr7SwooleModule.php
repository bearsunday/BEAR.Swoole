<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\HttpMessage\RequestProviderInterface;

class Psr7SwooleModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(RequestProviderInterface::class)->to(SwooleRequestProvider::class);
        // generic cache for user
        $this->bind(Cache::class)->toProvider(CacheProvider::class)->in(Scope::SINGLETON);
        // prod annotation reader
        $this->bind(Cache::class)->annotatedWith('annotation_cache')->toProvider(CacheProvider::class)->in(Scope::SINGLETON);
        $this->bind(Reader::class)->toConstructor(
            CachedReader::class,
            'reader=annotation_reader'
        );
    }
}
