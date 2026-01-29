<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\RepositoryModule\Annotation\EtagPool;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\HttpMessage\Annotation\UploadFiles;
use Ray\HttpMessage\RequestProviderInterface;
use Ray\PsrCacheModule\Annotation\Shared;

final class SwooleModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new \Ray\HttpMessage\Psr7Module());
        $this->bind()->annotatedWith('cache_namespace')->toInstance('');
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(Shared::class)->toProvider(CacheProvider::class)->in(Scope::SINGLETON);
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(EtagPool::class)->toProvider(CacheProvider::class)->in(Scope::SINGLETON);
        $this->bind(HttpCacheInterface::class)->to(HttpCache::class);
        // PSR-17 factories for SwooleServerRequestConverter
        $this->bind(Psr17Factory::class)->in(Scope::SINGLETON);
        $this->bind(ServerRequestFactoryInterface::class)->to(Psr17Factory::class)->in(Scope::SINGLETON);
        $this->bind(UriFactoryInterface::class)->to(Psr17Factory::class)->in(Scope::SINGLETON);
        $this->bind(UploadedFileFactoryInterface::class)->to(Psr17Factory::class)->in(Scope::SINGLETON);
        $this->bind(StreamFactoryInterface::class)->to(Psr17Factory::class)->in(Scope::SINGLETON);
        $this->bind(SwooleServerRequestConverter::class)->in(Scope::SINGLETON);
        $this->bind(SwooleRequestProvider::class)->in(Scope::SINGLETON);
        $this->bind(RequestProviderInterface::class)->to(SwooleRequestProvider::class);
        $this->bind(ServerRequestInterface::class)->toProvider(SwooleRequestProvider::class)->in(Scope::SINGLETON);
        $this->bind(UriInterface::class)->toProvider(SwooleUriProvider::class);
        $this->bind()->annotatedWith(UploadFiles::class)->toProvider(SwooleUploadfilesProvider::class);
        $this->bind(TransferInterface::class)->to(Responder::class);
        $this->bind(Error::class);
    }
}
