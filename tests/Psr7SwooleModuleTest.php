<?php
namespace BEAR\Swoole;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Ray\Di\Injector;
use Swoole\Http\Request;

class Psr7SwooleModuleTest extends TestCase
{
    public function testPsr7SwooleModule()
    {
        $injector = new Injector(new Psr7SwooleModule);
        $requestProvider = $injector->getInstance(SwooleRequestProvider::class);
        $swooleContainer = $injector->getInstance(SwooleRequestContainer::class);
        $request = new Request;
        $request->get = [];
        $request->post = [];
        $request->server = [
            'request_method' => 'GET',
            'request_uri' => '/',
            'path_info' => '/',
        ];
        $request->header = [
        ];
        $request->cookie = [];
        $swooleContainer->set($request);
        $request = @$requestProvider->get();
        $this->assertInstanceOf(RequestInterface::class, $request);
    }
}
