<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class RequestBodyTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:8088',
            'http_errors' => false,
        ]);
    }

    public function testJsonPut(): void
    {
        $response = $this->client->put('/ticket', [
            'json' => ['title' => 'hello', 'qty' => 3],
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('{
    "title": "hello",
    "qty": 3
}
', (string) $response->getBody());
    }

    public function testJsonPost(): void
    {
        $response = $this->client->post('/ticket', [
            'json' => ['title' => 'world', 'qty' => 5],
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('{
    "title": "world",
    "qty": 5
}
', (string) $response->getBody());
    }

    public function testMalformedJsonReturns400(): void
    {
        $response = $this->client->put('/ticket', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"title":}',
        ]);
        $this->assertSame(400, $response->getStatusCode());
        $alive = $this->client->get('/');
        $this->assertSame(200, $alive->getStatusCode(), 'Server must survive a malformed JSON request');
    }

    public function testEmptyJsonBodyReturns400(): void
    {
        $response = $this->client->put('/ticket', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $this->assertSame(400, $response->getStatusCode());
        $alive = $this->client->get('/');
        $this->assertSame(200, $alive->getStatusCode(), 'Server must survive an empty JSON request');
    }

    /** A syntactically valid but non-object JSON body (e.g. 123) makes the router throw a TypeError, an Error not an Exception */
    public function testScalarJsonBodyDoesNotCrashServer(): void
    {
        $response = $this->client->put('/ticket', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '123',
        ]);
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $alive = $this->client->get('/');
        $this->assertSame(200, $alive->getStatusCode(), 'Server must survive a scalar JSON body');
    }

    /** An unknown method override makes Method::from() throw a ValueError, an Error not an Exception */
    public function testInvalidMethodOverrideDoesNotCrashServer(): void
    {
        $response = $this->client->post('/', [
            'headers' => ['X-HTTP-Method-Override' => 'FOO'],
        ]);
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $alive = $this->client->get('/');
        $this->assertSame(200, $alive->getStatusCode(), 'Server must survive an invalid method override');
    }

    /** A Raw-Post-Data header must not be honoured as the request body (empty real body) */
    public function testRawPostDataHeaderCannotInjectBody(): void
    {
        $response = $this->client->put('/ticket', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Raw-Post-Data' => '{"title":"x","qty":1}',
            ],
        ]);
        $this->assertSame(400, $response->getStatusCode());
        $alive = $this->client->get('/');
        $this->assertSame(200, $alive->getStatusCode(), 'Server must survive a Raw-Post-Data header');
    }
}
