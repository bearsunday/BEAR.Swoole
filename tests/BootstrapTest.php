<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:8088',
        ]);
    }

    public function test200(): void
    {
        $response = $this->client->get('/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{
    "greeting": "Hello BEAR.Sunday"
}
', (string) $response->getBody());
    }

    public function test304(): void
    {
        $response1 = $this->client->get('/cache');
        $etag = $response1->getHeader('Etag');
        $response2 = $this->client->get('/cache', ['headers' => ['if-none-match' => $etag]]);
        $this->assertSame(304, $response2->getStatusCode());
        $this->assertSame('', (string) $response2->getBody());
    }



    /** test @CookieParam, @FormParam, @QueryParam, @ServerParam annotated web context injection */
    public function testPsr7ServerRequest(): void
    {
        $jar = CookieJar::fromArray([
            'c' => 'cookie_value',
        ], '127.0.0.1');
        $response = $this->client->post('/psr7?q=query_value', [
            'cookies' => $jar,
            'form_params' => ['f' => 'form_value'],
            'headers' => ['x-my-header' => 'header_value'],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{
    "cookie": "cookie_value",
    "form": "form_value",
    "query": "query_value",
    "header": [
        "header_value"
    ]
}
', (string) $response->getBody());
    }
}
