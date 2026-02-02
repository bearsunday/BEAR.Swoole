<?php

declare(strict_types=1);

namespace BEAR\Skeleton\Resource\Page;

use BEAR\Resource\ResourceObject;
use Ray\WebContextParam\Annotation\CookieParam;
use Ray\WebContextParam\Annotation\FormParam;
use Ray\WebContextParam\Annotation\QueryParam;
use Ray\WebContextParam\Annotation\ServerParam;

class WebContext extends ResourceObject
{
    public function onPost(
        #[QueryParam('q')]
        string $query,
        #[FormParam('f')]
        string $form,
        #[CookieParam('c')]
        string $cookie,
        #[ServerParam('REQUEST_METHOD')]
        string $method,
    ): static {
        $this->body = [
            'query' => $query,
            'form' => $form,
            'cookie' => $cookie,
            'method' => $method,
        ];

        return $this;
    }
}
