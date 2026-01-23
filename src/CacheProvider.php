<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Ray\Di\ProviderInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** @implements ProviderInterface<ArrayAdapter> */
class CacheProvider implements ProviderInterface
{
    public function get(): ArrayAdapter
    {
        return new ArrayAdapter(0, false);
    }
}
