<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Swoole\Http\Response;

interface HttpCacheInterface
{
    /** @param array<string, string> $server */
    public function isNotModified(array $server): bool;

    public function transfer(Response $response): void;
}
