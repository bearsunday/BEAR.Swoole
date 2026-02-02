<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Swoole\Http\Response;

interface HttpCacheInterface
{
    public function isNotModified(): bool;

    public function transfer(Response $response): void;
}
