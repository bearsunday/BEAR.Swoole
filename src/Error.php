<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException as BadRequest;
use BEAR\Resource\Exception\ResourceNotFoundException as NotFound;
use BEAR\Resource\Exception\ServerErrorException as ServerError;
use Exception;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function array_key_exists;
use function error_log;
use function json_encode;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

final class Error
{
    public function transfer(Exception $e, Request $request, Response $response): void
    {
        $this->log($request, $e);
        $response->header('content-type', 'text/plain');
        if ($this->isCodeExists($e)) {
            $code = $e->getCode();
            $response->status($code);
            $response->end(sprintf("%s %s\n", (string) $code, (new Code())->statusText[$code]));

            return;
        }

        $response->status(500);
        $response->end('500 Server Error' . PHP_EOL);
    }

    private function log(Request $request, Exception $e): void
    {
        error_log(
            (string) json_encode(
                [
                    'code' => $e->getCode(),
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'method' => $request->server['request_method'],
                    'uri' => $request->server['request_uri'],
                    'get' => $request->get,
                    'post' => $request->post,
                ],
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            ),
        );
    }

    private function isCodeExists(Exception $e): bool
    {
        if (! ($e instanceof NotFound) && ! ($e instanceof BadRequest) && ! ($e instanceof ServerError)) {
            return false;
        }

        return array_key_exists($e->getCode(), (new Code())->statusText);
    }
}
