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
        #[CookieParam('c')] string $cookie,
        #[FormParam('f')] string $form,
        #[QueryParam('q')] string $query,
        #[ServerParam('HTTP_X_MY_HEADER')] string $header,
    ): static {
        $this->body = [
            'cookie' => $cookie,
            'form' => $form,
            'query' => $query,
            'header' => $header,
        ];

        return $this;
    }
}
