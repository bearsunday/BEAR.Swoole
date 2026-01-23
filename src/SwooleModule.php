<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\Resource\TransferInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\HttpMessage\RequestProviderInterface;
use Ray\PsrCacheModule\Annotation\Shared;

final class SwooleModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind()->annotatedWith('cache_namespace')->toInstance('');
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(Shared::class)->toProvider(CacheProvider::class)->in(Scope::SINGLETON);
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(EtagPool::class)->toProvider(CacheProvider::class)->in(Scope::SINGLETON);
        $this->bind(CacheItemInterface::class)->toProvider(CacheProvider::class);
        $this->bind(HttpCacheInterface::class)->to(HttpCache::class);
        $this->bind(RequestProviderInterface::class)->to(SwooleRequestProvider::class);
        $this->bind(TransferInterface::class)->to(Responder::class);
        $this->bind(Error::class);
    }
}
