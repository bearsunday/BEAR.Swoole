<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Application\AbstractApp;
use BEAR\Sunday\Extension\Router\RouterInterface;

final class App extends AbstractApp
{
    /**
     * @var HttpCache
     */
    public $httpCache;

    /**
     * @var RouterInterface
     */
    public $router;

    /**
     * @var Responder
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
        HttpCache $httpCache,
        RouterInterface $router,
        Responder $responder,
        ResourceInterface $resource,
        Error $error
    )
    {
        $this->httpCache = $httpCache;
        $this->router = $router;
        $this->responder = $responder;
        $this->resource = $resource;
        $this->error = $error;
    }
}
