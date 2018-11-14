<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Swoole\Http\Request;

/**
 * Web context
 *
 * PHP "Superglobals" for RouterInterface
 */
final class WebContext
{
    /**
     * $_SERVER for router
     *
     * @var array
     */
    public $server;

    /**
     * $GLOBALS for router
     *
     * @var array
     */
    public $globals;

    public function __construct(Request $request)
    {
        $this->server = [
            'REQUEST_METHOD' => $request->server['request_method'],
            'REQUEST_URI' => $request->server['request_uri'],
            'CONTENT_TYPE' => $request->header['content-type'] ?? '',
            'HTTP_RAW_POST_DATA' => $request->rawcontent()
        ];
        $this->globals = [
            '_GET' => $request->get ?? [],
            '_POST' => $request->post ?? []
        ];
    }
}
