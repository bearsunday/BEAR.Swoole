<?php
namespace BEAR\Swoole;

use Swoole\Http\Request;

final class SwooleRequestContainer
{
    private $request;

    public function set(Request $request) : void
    {
        $this->request = $request;
    }

    public function get() : Request
    {
        if (! $this->request instanceof Request) {
            throw new \LogicException('Swoole request is unset.');
        }

        return $this->request;
    }
}
