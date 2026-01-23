<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\QueryRepository\ResourceStorageInterface;
use Swoole\Http\Response;

final readonly class HttpCache implements HttpCacheInterface
{
    public function __construct(
        private ResourceStorageInterface $storage,
    ) {
    }

    /** @param array<string, string> $server */
    public function isNotModified(array $server): bool
    {
        return isset($server['if-none-match']) && $this->storage->hasEtag($server['if-none-match']);
    }

    public function transfer(Response $response): void
    {
        $response->status(304);
        $response->end('');
    }
}
