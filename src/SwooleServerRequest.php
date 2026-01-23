<?php

/**
 * SwooleServerRequest
 *
 * Taken from https://www.github.com/janhuang and modified
 * Original author: jan huang <bboyjanhuang@gmail.com>
 */

declare(strict_types=1);

namespace BEAR\Swoole;

use FastD\Http\ServerRequest;
use Swoole\Http\Request;

use function explode;
use function parse_url;
use function str_replace;

use const PHP_URL_HOST;
use const SWOOLE_VERSION;

/** @SuppressWarnings(PHPMD) */
class SwooleServerRequest extends ServerRequest
{
    public static function createServerRequestFromSwoole(Request $request): ServerRequest
    {
        $get = $request->get ?? [];
        $post = $request->post ?? [];
        $cookie = $request->cookie ?? [];
        $files = $request->files ?? [];

        $host = '::1';
        foreach (['host', 'server_addr'] as $name) {
            if (! empty($request->header[$name])) {
                $host = parse_url($request->header[$name], PHP_URL_HOST) ?: $request->header[$name];
            }
        }

        $serverProtocol = $request->server['server_protocol'] ?? 'HTTP/1.1';
        $server = [
            'REQUEST_METHOD' => $request->server['request_method'],
            'REQUEST_URI' => $request->server['request_uri'],
            'PATH_INFO' => $request->server['path_info'] ?? '',
            'REQUEST_TIME' => $request->server['request_time'] ?? time(),
            'GATEWAY_INTERFACE' => 'swoole/' . SWOOLE_VERSION,
            // Server
            'SERVER_PROTOCOL' => $request->header['server_protocol'] ?? $serverProtocol,
            'REQUEST_SCHEMA' => $request->header['request_scheme'] ?? explode('/', $serverProtocol)[0],
            'SERVER_NAME' => $request->header['server_name'] ?? $host,
            'SERVER_ADDR' => $host,
            'SERVER_PORT' => $request->header['server_port'] ?? $request->server['server_port'] ?? 80,
            'REMOTE_ADDR' => $request->server['remote_addr'] ?? '127.0.0.1',
            'REMOTE_PORT' => $request->header['remote_port'] ?? $request->server['remote_port'] ?? 0,
            'QUERY_STRING' => $request->server['query_string'] ?? '',
            // Headers
            'HTTP_HOST' => $host,
            'HTTP_USER_AGENT' => $request->header['user-agent'] ?? '',
            'HTTP_ACCEPT' => $request->header['accept'] ?? '*/*',
            'HTTP_ACCEPT_LANGUAGE' => $request->header['accept-language'] ?? '',
            'HTTP_ACCEPT_ENCODING' => $request->header['accept-encoding'] ?? '',
            'HTTP_CONNECTION' => $request->header['connection'] ?? '',
            'HTTP_CACHE_CONTROL' => $request->header['cache-control'] ?? '',
        ];

        $headers = [];
        foreach ($request->header as $name => $value) {
            $headers[str_replace('-', '_', (string) $name)] = $value;
        }

        $serverRequest = new ServerRequest(
            $server['REQUEST_METHOD'],
            static::createUriFromGlobal($server),
            $headers,
            null,
            $server,
        );
        unset($headers);

        $serverRequest->getBody()->write((string) $request->rawContent());

        return $serverRequest
            ->withParsedBody($post)
            ->withQueryParams($get)
            ->withCookieParams($cookie)
            ->withUploadedFiles($files);
    }
}
