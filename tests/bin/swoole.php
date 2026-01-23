<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

exit((require dirname(__DIR__, 2) . '/bootstrap.php')(
    'prod-app',       // context
    'BEAR\Skeleton',  // application name
    '127.0.0.1',      // IP
    8088,             // port
));
