<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Ray\Di\Injector;
use Swoole\Coroutine\WaitGroup;
use Swoole\Http\Request;

class Psr7SwooleModuleTest extends TestCase
{
    public function testToGlobalsWithContentTypeAndContentLength(): void
    {
        $request = new Request();
        $request->server = ['request_method' => 'POST'];
        $request->header = [
            'content-type' => 'application/json',
            'content-length' => '123',
            'x-custom' => 'value',
        ];

        $server = SwooleServerRequestConverter::toGlobals($request);

        $this->assertSame('application/json', $server['CONTENT_TYPE']);
        $this->assertSame('123', $server['CONTENT_LENGTH']);
        $this->assertSame('value', $server['HTTP_X_CUSTOM']);
    }

    public function testCreateFromSwooleWithHttps(): void
    {
        /** @phpstan-ignore-next-line function.notFound */
        \Co\run(function (): void {
            $injector = new Injector(new SwooleModule());
            $request = new Request();
            $request->get = [];
            $request->post = [];
            $request->server = [
                'request_method' => 'GET',
                'request_uri' => '/secure',
                'https' => 'on',
                'server_port' => 443,
            ];
            $request->header = ['host' => 'example.com'];
            $request->cookie = [];

            SwooleRequestProvider::seed($request);
            $serverRequest = $injector->getInstance(ServerRequestInterface::class);

            $this->assertSame('https', $serverRequest->getUri()->getScheme());
        });
    }

    public function testCreateFromSwooleWithXForwardedProto(): void
    {
        /** @phpstan-ignore-next-line function.notFound */
        \Co\run(function (): void {
            $injector = new Injector(new SwooleModule());
            $request = new Request();
            $request->get = [];
            $request->post = [];
            $request->server = [
                'request_method' => 'GET',
                'request_uri' => '/proxy',
            ];
            $request->header = [
                'host' => 'example.com',
                'x-forwarded-proto' => 'https',
            ];
            $request->cookie = [];

            SwooleRequestProvider::seed($request);
            $serverRequest = $injector->getInstance(ServerRequestInterface::class);

            $this->assertSame('https', $serverRequest->getUri()->getScheme());
        });
    }

    public function testCreateFromSwooleWithServerName(): void
    {
        /** @phpstan-ignore-next-line function.notFound */
        \Co\run(function (): void {
            $injector = new Injector(new SwooleModule());
            $request = new Request();
            $request->get = [];
            $request->post = [];
            $request->server = [
                'request_method' => 'GET',
                'request_uri' => '/',
                'server_name' => 'fallback.example.com',
                'server_port' => 8080,
            ];
            $request->header = [];
            $request->cookie = [];

            SwooleRequestProvider::seed($request);
            $serverRequest = $injector->getInstance(ServerRequestInterface::class);

            $this->assertSame('fallback.example.com', $serverRequest->getUri()->getHost());
            $this->assertSame(8080, $serverRequest->getUri()->getPort());
        });
    }

    public function testCreateFromSwooleWithoutRequestMethod(): void
    {
        /** @phpstan-ignore-next-line function.notFound */
        \Co\run(function (): void {
            $injector = new Injector(new SwooleModule());
            $request = new Request();
            $request->get = [];
            $request->post = [];
            $request->server = [
                'request_uri' => '/',
            ];
            $request->header = [];
            $request->cookie = [];

            SwooleRequestProvider::seed($request);
            $serverRequest = $injector->getInstance(ServerRequestInterface::class);

            $this->assertSame('GET', $serverRequest->getMethod());
        });
    }

    public function testPsr7SwooleModule(): void
    {
        /** @phpstan-ignore-next-line function.notFound */
        \Co\run(function (): void {
            $injector = new Injector(new SwooleModule());
            $requestProvider = $injector->getInstance(SwooleRequestProvider::class);
            $request = new Request();
            $request->get = [];
            $request->post = [];
            $request->server = [
                'request_method' => 'GET',
                'request_uri' => '/',
                'path_info' => '/',
            ];
            $request->header = [];
            $request->cookie = [];
            SwooleRequestProvider::seed($request);
            $this->assertInstanceOf(RequestInterface::class, $requestProvider->get());

            // New bindings
            $instance1 = $injector->getInstance(ServerRequestInterface::class);
            $instance2 = $injector->getInstance(ServerRequestInterface::class);
            $this->assertInstanceOf(ServerRequestInterface::class, $instance1);
            $this->assertSame($instance1, $instance2); // Should be same in same coroutine due to context caching
            $this->assertInstanceOf(UriInterface::class, $injector->getInstance(UriInterface::class));

            // Child coroutine mobility test
            $childRequest = null;
            $wg = new WaitGroup();
            $wg->add();
            go(static function () use (&$childRequest, $injector, $wg): void {
                $childRequest = $injector->getInstance(ServerRequestInterface::class);
                $wg->done();
            });
            $wg->wait();

            $this->assertInstanceOf(ServerRequestInterface::class, $childRequest);
            $this->assertSame($instance1->getMethod(), $childRequest->getMethod());
        });
    }

    public function testConcurrentRequestIsolation(): void
    {
        /** @phpstan-ignore-next-line function.notFound */
        \Co\run(function (): void {
            $injector = new Injector(new SwooleModule());
            $iterations = 100;
            $results = [];
            $wg = new WaitGroup();

            for ($i = 0; $i < $iterations; $i++) {
                $wg->add();
                $index = $i;
                $isPost = $i % 2 === 0;

                go(static function () use (&$results, $injector, $index, $isPost, $wg): void {
                    $request = new Request();
                    $request->get = $isPost ? [] : ['id' => (string) $index];
                    $request->post = $isPost ? ['id' => (string) $index] : [];
                    $request->server = [
                        'request_method' => $isPost ? 'POST' : 'GET',
                        'request_uri' => '/test/' . $index,
                        'path_info' => '/test/' . $index,
                    ];
                    $request->header = [];
                    $request->cookie = [];

                    SwooleRequestProvider::seed($request);

                    // Add small random delay to increase chance of race condition
                    usleep(random_int(0, 1000));

                    $serverRequest = $injector->getInstance(ServerRequestInterface::class);

                    $parsedBody = $serverRequest->getParsedBody();
                    $postId = is_array($parsedBody) ? ($parsedBody['id'] ?? null) : null;

                    $results[$index] = [
                        'expected_method' => $isPost ? 'POST' : 'GET',
                        'actual_method' => $serverRequest->getMethod(),
                        'expected_uri' => '/test/' . $index,
                        'actual_uri' => $serverRequest->getUri()->getPath(),
                        'expected_id' => (string) $index,
                        'actual_id' => $isPost
                            ? $postId
                            : ($serverRequest->getQueryParams()['id'] ?? null),
                    ];

                    $wg->done();
                });
            }

            $wg->wait();

            // Verify all requests maintained isolation
            $this->assertCount($iterations, $results);

            foreach ($results as $index => $result) {
                $this->assertSame(
                    $result['expected_method'],
                    $result['actual_method'],
                    "Request {$index}: Method mismatch - expected {$result['expected_method']}, got {$result['actual_method']}"
                );
                $this->assertSame(
                    $result['expected_uri'],
                    $result['actual_uri'],
                    "Request {$index}: URI mismatch"
                );
                $this->assertSame(
                    $result['expected_id'],
                    $result['actual_id'],
                    "Request {$index}: ID mismatch - expected {$result['expected_id']}, got " . ($result['actual_id'] ?? 'null')
                );
            }
        });
    }
}
