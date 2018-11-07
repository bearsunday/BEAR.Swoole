<?php

use BEAR\Package\AppInjector;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Router\RouterMatch;
use BEAR\Sunday\Provide\Error\VndError;
use BEAR\Swoole\App;
use Swoole\Http\Request;
use Swoole\Http\Response;

return function (string $context, string $name, string $ip, string $port) use ($responder) : int {
    if (! class_exists('swoole_http_server')) {
        throw new \RuntimeException('Swoole is not installed. See https://github.com/swoole/swoole-src/wiki/Installing');
    }
    $http = new swoole_http_server($ip, $port);
    $http->on("start", function ($server) use ($ip, $port) {
        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;
    });
    $injector = new AppInjector($name, $context);
    /* @var App $app */
    $app = $injector->getInstance(App::class);
    $http->on("request", function (Request $request, Response $response) use ($app) {
        if ($app->httpCache->isNotModified($request->header)) {
            $app->httpCache->transfer($response);

            return;
        }
        $method = strtolower($request->server['request_method']);
        $query = $method === 'get' ? $request->get : $request->post;
        $path = 'page://self'. $request->server['request_uri'];
        try {
            /* @var ResourceObject $ro */
            $ro = $app->resource->{$method}->uri($path)($query);
            ($app->responder)($ro, $response);
        } catch (\Exception $e) {
            $app->error->transfer($e, $request, $response);
        }
    });
    $http->start();
};
