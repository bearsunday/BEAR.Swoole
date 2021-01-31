<?php
namespace BEAR\Skeleton\Resource\Page;

use BEAR\Resource\ResourceObject;
use Ray\WebContextParam\Annotation\CookieParam;
use Ray\WebContextParam\Annotation\FormParam;
use Ray\WebContextParam\Annotation\QueryParam;
use Ray\WebContextParam\Annotation\ServerParam;

class WebContext extends ResourceObject
{
    /**
     * @CookieParam(param="cookie", key="c")
     * @FormParam(param="form", key="f")
     * @QueryParam(param="query", key="q")
     * @ServerParam(param="header", key="HTTP_X_MY_HEADER")
     */
    public function onPost(string $cookie, string $form, string $query, string $header): ResourceObject
    {
        $this->body = [
            'cookie' => $cookie,
            'form' => $form,
            'query' => $query,
            'header' => $header
        ];

        return $this;
    }
}
