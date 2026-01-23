<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\TransferInterface;
use BEAR\Sunday\Extension\Router\RouterInterface;

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
