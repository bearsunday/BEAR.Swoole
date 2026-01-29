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

    /** @param array<string, mixed> $server */
    public function isNotModified(array $server): bool
    {
        $etag = $server['HTTP_IF_NONE_MATCH'] ?? null;

        return is_string($etag) && $this->storage->hasEtag($etag);
    }

    public function transfer(Response $response): void
    {
        $response->status(304);
        $response->end('');
    }
}
