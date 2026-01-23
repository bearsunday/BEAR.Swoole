<?php

declare(strict_types=1);

namespace BEAR\Skeleton\Resource\Page;

use BEAR\Resource\ResourceObject;
use Ray\HttpMessage\RequestProviderInterface;

use function assert;
use function is_array;

class Psr7 extends ResourceObject
{
    public function __construct(
        private readonly RequestProviderInterface $requestProvider,
    ) {
    }

    public function onPost(): static
    {
        $serverReuquest = $this->requestProvider->get();
        $body = $serverReuquest->getParsedBody();
        assert(is_array($body));
        $this->body = [
            'cookie' => $serverReuquest->getCookieParams()['c'],
            'form' => $body['f'],
            'query' => $serverReuquest->getQueryParams()['q'],
            'header' => $serverReuquest->getHeader('x_my_header'),
        ];

        return $this;
    }
}
