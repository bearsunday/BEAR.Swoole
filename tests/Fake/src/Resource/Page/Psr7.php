<?php

declare(strict_types=1);

namespace BEAR\Skeleton\Resource\Page;

use BEAR\Resource\ResourceObject;
use Psr\Http\Message\ServerRequestInterface;

use function assert;
use function is_array;

class Psr7 extends ResourceObject
{
    public function __construct(
        private readonly ServerRequestInterface $serverRequest,
    ) {
    }

    public function onPost(): static
    {
        $body = $this->serverRequest->getParsedBody();
        assert(is_array($body));
        $this->body = [
            'cookie' => $this->serverRequest->getCookieParams()['c'],
            'form' => $body['f'],
            'query' => $this->serverRequest->getQueryParams()['q'],
            'header' => $this->serverRequest->getHeader('x-my-header'),
        ];

        return $this;
    }
}
