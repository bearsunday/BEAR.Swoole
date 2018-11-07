<?php

use BEAR\Package\Bootstrap;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Router\RouterMatch;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use BEAR\Sunday\Provide\Error\VndError;
use Swoole\Http\Request;
use Swoole\Http\Response;

$responder = require __DIR__ . 'responder.php';

return function (string $context, string $name, string $ip, string $port) use ($responder) : int {
    if (! class_exists('swoole_http_server')) {
        throw new \RuntimeException('Swoole is not installed. See https://github.com/swoole/swoole-src/wiki/Installing');
    }
    $http = new swoole_http_server($ip, $port);
    $http->on("start", function ($server) use ($ip, $port) {
        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;
    });
    $app = (new Bootstrap)->getApp($name, $context);
    $http->on("request", function (Request $request, Response $response) use ($app, $responder) {
        if ($app->httpCache->isNotModified($request->server)) {
            $response->status(304);
            $response->end('');
        }
        $method = strtolower($request->server['request_method']);
        $query = $method === 'get' ? $request->get : $request->post;
        $path = 'page://self'. $request->server['request_uri'];
        try {
            /* @var ResourceObject $ro */
            $ro = $app->resource->{$method}->uri($path)($query);
            $responder->setResponse($response);
            $ro->transfer($responder, $_SERVER);
        } catch (\Exception $e) {
            echo (string) $e . PHP_EOL; // on server screen
            $match = new RouterMatch;
            [$match->method, $match->path, $match->query] = [$method, $path, $query];
            (new VndError($responder))->handle($e, $match)->transfer();
        }
    });
    $http->start();
};
