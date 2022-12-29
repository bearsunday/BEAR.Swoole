<?php

declare(strict_types=1);

use BEAR\Resource\ResourceObject;
use BEAR\Swoole\App;
use BEAR\Swoole\SuperGlobals;
use BEAR\Swoole\SwooleModule;
use Ray\Di\Injector;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use BEAR\AppMeta\Meta;
use BEAR\Package\Module;

if (! class_exists(Server::class)) {
    throw new \RuntimeException('Swoole is not installed. See https://github.com/swoole/swoole-src/wiki/Installing');
}

return function (
    string $context,
    string $name,
    string $ip,
    int $port,
    int $mode = SWOOLE_PROCESS,
    int $sockType = SWOOLE_SOCK_TCP,
    array $settings = ['worker_num' => 4]
) : int {
    $http = new Server($ip, $port, $mode, $sockType);
    $http->set($settings);
    $http->on('start', function () use ($ip, $port) {
        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;

        return 1;
    });
    $appModule = (new Module)(new Meta($name, $context), $context);
    $appModule->override(new SwooleModule());
    $injector = new Injector(new SwooleModule($appModule));
    $app = $injector->getInstance(App::class);
    $superGlobals = new SuperGlobals;
    $http->on('request', function (Request $request, Response $response) use ($app, $superGlobals) {
        if ($app->httpCache->isNotModified($request->header)) {
            $app->httpCache->transfer($response);

            return;
        }
        $superGlobals($request);
        $match = $app->router->match($GLOBALS, $_SERVER);
        try {
            /* @var ResourceObject $ro */
            $ro = $app->resource->{$match->method}->uri($match->path)($match->query);
            $app->responder->setResponse($response);
            $ro->transfer($app->responder, []);
        } catch (\Exception $e) {
            $app->error->transfer($e, $request, $response);
        }
    });
    $http->start();

    return 0;
};
