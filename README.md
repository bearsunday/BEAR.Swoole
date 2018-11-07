# BEAR.Swoole

This library provides the support of Swoole into an BEAR.Sunday application.

## Installation

Run the following to install this library:

    composer require bear/swoole


## Entry Script

Place the entry script file at `bin/swoole.php` with IP address and port number.

```php
<?php
require dirname(__DIR__) . '/autoload.php';
exit((require dirname(__DIR__) . '/vendor/bear/swoole/bootstrap.php')(
    'prod-hal-app',
    'BEAR\Skeleton',
    '127.0.0.1',
    '8080'
));
```


## Execute

You can run an BEAR.Sunday application with Swoole using the following command:


    php bin/swoole.php