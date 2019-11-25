<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\TransferInterface;
use Swoole\Http\Response;

final class Responder implements TransferInterface
{
    /**
     * @var Response
     */
    private $response;

    public function __invoke(ResourceObject $ro, array $server)
    {
        unset($server);
        $ro->toString();
        foreach ($ro->headers as $key => $value) {
            $this->response->header($key, (string) $value);
        }
        $this->response->status($ro->code);
        $this->response->end($ro->view);
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
}
