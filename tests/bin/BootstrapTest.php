<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    /**
     * @var Client
     */
    private $client;

    protected function setUp()
    {
        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:8088'
        ]);
    }

    public function testRequest()
    {
        $response = $this->client->get('/');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{
    "greeting": "Hello BEAR.Sunday"
}
', (string) $response->getBody());
    }
}
