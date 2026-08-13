<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Override;
use Psr\Http\Message\UriInterface;
use Ray\Di\ProviderInterface;

/**
 * @implements ProviderInterface<UriInterface>
 * @codeCoverageIgnore Swoole coroutine context only
 */
final readonly class SwooleUriProvider implements ProviderInterface
{
    public function __construct(
        private SwooleRequestProvider $requestProvider,
    ) {
    }

    #[Override]
    public function get(): UriInterface
    {
        return $this->requestProvider->get()->getUri();
    }
}
