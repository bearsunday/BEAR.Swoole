<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use BEAR\Sunday\Extension\Router\RouterInterface;

/**
 * @codeCoverageIgnore Swoole server context only
 */
final readonly class App
{
    public function __construct(
        public HttpCacheInterface $httpCache,
        public RouterInterface $router,
        public TransferInterface $responder,
        public ResourceInterface $resource,
        public Error $error,
    ) {
    }
}
