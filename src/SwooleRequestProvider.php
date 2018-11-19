<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use FastD\Http\SwooleServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Ray\HttpMessage\RequestProviderInterface;

final class SwooleRequestProvider implements RequestProviderInterface
{
    public function get() : ServerRequestInterface
    {
        return SwooleServerRequest::createServerRequestFromSwoole(SuperGlobals::$swooleRequest);
    }
}
