<?php

declare(strict_types=1);

use BEAR\Package\AppInjector;
use BEAR\Resource\ResourceObject;
use BEAR\Swoole\App;
use BEAR\Swoole\Psr7SwooleModule;
use BEAR\Swoole\SuperGlobals;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

if (! class_exists(Server::class)) {
    throw new \RuntimeException('Swoole is not installed. See https://github.com/swoole/swoole-src/wiki/Installing');
}

return function (
    string $context,
    string $name,
    string $ip,
    int $port,
    int $mode = SWOOLE_BASE,
    int $sockType = SWOOLE_SOCK_TCP,
    array $settings = ['worker_num' => 4]
) : int {
    $http = new Server($ip, $port, $mode, $sockType);
    $http->set($settings);
    $http->on('start', function () use ($ip, $port) {
        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;
    });
    $injector = new AppInjector($name, $context);
    /* @var App $app */
    $app = $injector->getOverrideInstance(new Psr7SwooleModule, App::class);
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
};
