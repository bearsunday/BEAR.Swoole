<?php
namespace BEAR\Skeleton\Resource\Page;

use BEAR\Resource\ResourceObject;
use Psr\Http\Message\ServerRequestInterface;
use Ray\HttpMessage\RequestProviderInterface;
use Ray\WebContextParam\Annotation\CookieParam;
use Ray\WebContextParam\Annotation\FormParam;
use Ray\WebContextParam\Annotation\QueryParam;
use Ray\WebContextParam\Annotation\ServerParam;

class Psr7 extends ResourceObject
{
    /**
     * @var RequestProviderInterface
     */
    private $requestProvider;

    public function __construct(RequestProviderInterface $requestProvider)
    {
        $this->requestProvider = $requestProvider;
    }

    public function onPost(): self
    {
        $serverReuquest = $this->requestProvider->get();
        $body = $serverReuquest->getParsedBody();
        assert(is_array($body));
        $this->body = [
            'cookie' => $serverReuquest->getCookieParams()['c'],
            'form' => $body['f'],
            'query' => $serverReuquest->getQueryParams()['q'],
            'header' => $serverReuquest->getHeader('x_my_header')
        ];

        return $this;
    }
}
