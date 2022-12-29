<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\TransferInterface;
use BEAR\Sunday\Extension\Router\RouterInterface;

final class App
{
    /**
     * @var HttpCacheInterface
     */
    public $httpCache;

    /**
     * @var RouterInterface
     */
    public $router;

    /**
     * @var TransferInterface
     */
    public $responder;

    /**
     * @var ResourceInterface
     */
    public $resource;

    /**
     * @var Error
     */
    public $error;

    public function __construct(
        HttpCacheInterface $httpCache,
        RouterInterface $router,
        TransferInterface $responder,
        ResourceInterface $resource,
        Error $error
    ) {
        $this->httpCache = $httpCache;
        $this->router = $router;
        $this->responder = $responder;
        $this->resource = $resource;
        $this->error = $error;
    }
}
