<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\QueryRepository\ResourceStorageInterface;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * @codeCoverageIgnore Swoole server context only
 */
final readonly class HttpCache implements HttpCacheInterface
{
    public function __construct(
        private ResourceStorageInterface $storage,
    ) {
    }

    public function isNotModified(): bool
    {
        $etag = $this->getIfNoneMatch();

        return $etag !== null && $this->storage->hasEtag($etag);
    }

    private function getIfNoneMatch(): ?string
    {
        /** @var ArrayObject<string, mixed>|null $context */
        $context = Coroutine::getContext();
        if ($context === null) {
            return null;
        }

        $request = $context[Request::class] ?? null;
        if (! $request instanceof Request) {
            return null;
        }

        $etag = $request->header['if-none-match'] ?? null;

        return is_string($etag) ? $etag : null;
    }

    public function transfer(Response $response): void
    {
        $response->status(304);
        $response->end('');
    }
}
