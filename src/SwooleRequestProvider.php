<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use FastD\Http\SwooleServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Ray\HttpMessage\RequestProviderInterface;

final class SwooleRequestProvider implements RequestProviderInterface
{
    /**
     * @var SwooleRequestContainer
     */
    private $container;

    public function __construct(SwooleRequestContainer $container)
    {
        $this->container = $container;
    }

    public function get() : ServerRequestInterface
    {
        return SwooleServerRequest::createServerRequestFromSwoole($this->container->get());
    }
}
