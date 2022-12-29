<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Psr\Http\Message\ServerRequestInterface;
use Ray\HttpMessage\RequestProviderInterface;

final class SwooleRequestProvider implements RequestProviderInterface
{
    public function get() : ServerRequestInterface
    {
        return SwooleServerRequest::createServerRequestFromSwoole(SuperGlobals::$swooleRequest); // @phpstan-ignore-line
    }
}
