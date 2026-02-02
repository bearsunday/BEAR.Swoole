# BEAR.Swoole

This library provides the support of Swoole into an BEAR.Sunday application.

##  Installation

Run the following to install this library:

    composer require bear/swoole

## Entry Script

Place the entry script file at `bin/swoole.php` with IP address and port number.

```php
<?php
require dirname(__DIR__) . '/autoload.php';
exit((require dirname(__DIR__) . '/vendor/bear/swoole/bootstrap.php')(
    'prod-hal-app',       // context
    'MyVendor\MyProject', // application name
    '127.0.0.1',          // IP
    8080                  // port
));
```

## Execute

You can run a BEAR.Sunday application with Swoole using the following command:

    php bin/swoole.php

## Request Access

In Swoole's long-running process, PHP superglobals (`$_SERVER`, `$_GET`, `$_POST`, etc.) are not available per-request. Use PSR-7 ServerRequest injection instead:

```php
use Psr\Http\Message\ServerRequestInterface;

class MyResource extends ResourceObject
{
    public function __construct(
        private ServerRequestInterface $request
    ) {}

    public function onGet(): static
    {
        $server = $this->request->getServerParams();
        $query = $this->request->getQueryParams();
        // ...
    }
}
```
