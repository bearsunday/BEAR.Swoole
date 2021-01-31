<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Swoole\Http\Request;

final class SuperGlobals
{
    /**
     * @var Request
     */
    public static $swooleRequest;

    /**
     * Set properties and $GLOBALS for conventional PHP application
     */
    public function __invoke(Request $request): void
    {
        self::$swooleRequest = $request;
        if (isset($request->server)) {
            foreach ($request->server as $key => $value) {
                $_SERVER[strtoupper($key)] = $value;
            }
        }
        if (isset($request->header)) {
            foreach ($request->header as $key => $value) {
                $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
                $_SERVER[$headerKey] = $value;
            }
        }
        $_COOKIE = $request->cookie;
        $GLOBALS['_SERVER'] = $_SERVER;
        $GLOBALS['_GET'] = $request->get ?? [];
        $GLOBALS['_POST'] = $request->post ?? [];
        $GLOBALS['_COOKIE'] = $request->cookie;
    }
}
