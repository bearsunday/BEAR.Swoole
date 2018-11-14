<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException as BadRequest;
use BEAR\Resource\Exception\ResourceNotFoundException as NotFound;
use BEAR\Resource\Exception\ServerErrorException as ServerError;
use Swoole\Http\Request;
use Swoole\Http\Response;
use const JSON_PRETTY_PRINT;
use const PHP_EOL;
use function get_class;
use function json_encode;

final class Error
{
    public function transfer(\Exception $e, Request $request, Response $response)
    {
        $this->log($request, $e);
        $response->header('content-type', 'text/plain');
        if ($this->isCodeExists($e)) {
            $code = $e->getCode();
            $response->status($code);
            $response->end(sprintf("%s %s\n", (string) $code, (new Code)->statusText[$code]));

            return;
        }
        $response->status(500);
        $response->end('500 Server Error' . PHP_EOL);
    }

    private function log(Request $request, \Exception $e)
    {
        error_log(json_encode([
            'code' => $e->getCode(),
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'method' => $request->server['request_method'],
            'uri' => $request->server['request_uri'],
            'get' => $request->get,
            'post' => $request->post
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    private function isCodeExists(\Exception $e) : bool
    {
        if (! ($e instanceof NotFound) && ! ($e instanceof BadRequest) && ! ($e instanceof ServerError)) {
            return false;
        }

        return \array_key_exists($e->getCode(), (new Code)->statusText);
    }
}
