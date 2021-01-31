<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\QueryRepository\ResourceStorageInterface;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use Swoole\Http\Response;

final class HttpCache
{
    /**
     * @var ResourceStorageInterface
     */
    private $storage;

    public function __construct(ResourceStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param array<string, string> $server
     */
    public function isNotModified(array $server) : bool
    {
        return isset($server['if-none-match']) && $this->storage->hasEtag($server['if-none-match']);
    }

    public function transfer(Response $response): void
    {
        $response->status(304);
        $response->end('');
    }
}
