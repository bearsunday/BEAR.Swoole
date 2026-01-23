<?php

declare(strict_types=1);

use BEAR\Swoole\SwooleServer;

require dirname(__DIR__) . '/vendor/autoload.php';

(new SwooleServer(__DIR__ . '/bin/swoole.php'))->start();
