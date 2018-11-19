<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\HttpMessage\RequestProviderInterface;

class Psr7SwooleModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(RequestProviderInterface::class)->to(SwooleRequestProvider::class);
    }
}
