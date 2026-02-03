<?php

declare(strict_types=1);

use BEAR\AppMeta\Meta;
use BEAR\Package\Module;
use BEAR\Swoole\App;
use BEAR\Swoole\SwooleModule;
use BEAR\Resource\Method;
use BEAR\Swoole\SwooleRequestProvider;
use Ray\Di\Injector;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

// Enable coroutine hooks for all I/O operations (PDO, MySQL, Redis, curl, file, etc.)
Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);

/**
 * @return int Exit code
 */
return static function (string $context, string $name, string $ip, int $port, array $settings = []): int {
    $appModule = (new Module())(new Meta($name, $context), $context);
    $appModule->override(new SwooleModule());
    $injector = new Injector(new SwooleModule($appModule));
    $app = $injector->getInstance(App::class);

    $http = new Server($ip, $port);
    $http->set($settings);

    $http->on('start', static function (Server $server) use ($ip, $port): void {
        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;
    });

    $http->on('request', static function (Request $request, Response $response) use ($app): void {
        // Seed the context for potential PSR-7 use. Conversion is lazy.
        $server = SwooleRequestProvider::seed($request);

        // Check ETag from coroutine context directly.
        if ($app->httpCache->isNotModified()) {
            $app->httpCache->transfer($response);
            return;
        }

        $match = $app->router->match(
            [
                '_GET' => $request->get ?? [],
                '_POST' => $request->post ?? []
            ],
            $server
        );

        try {
            $ro = $app->resource->newRequest(Method::from($match->method), $match->path, $match->query)();

            $app->responder->setResponse($response);
            $ro->transfer($app->responder, []);

        } catch (Exception $e) {
            $app->error->transfer($e, $request, $response);
        }
    });

    $http->start();

    return 0;
};
