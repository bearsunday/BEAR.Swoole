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

    public function onPost()
    {
        $serverReuquest = $this->requestProvider->get();
        \var_dump( $serverReuquest->getHeaders());
        $this->body = [
            'cookie' => $serverReuquest->getCookieParams()['c'],
            'form' => $serverReuquest->getParsedBody()['f'],
            'query' => $serverReuquest->getQueryParams()['q'],
            'header' => $serverReuquest->getHeader('x_my_header')
        ];

        return $this;
    }
}
