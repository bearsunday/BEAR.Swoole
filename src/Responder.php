<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\ResourceObject;
use Swoole\Http\Response;

final class Responder
{
    public function __invoke(ResourceObject $ro, Response $response)
    {
        $ro->toString();
        foreach ($ro->headers as $key => $value) {
            $response->header($key, (string)$value);
        }
        $response->status($ro->code);
        $response->end($ro->view);
    }
}
