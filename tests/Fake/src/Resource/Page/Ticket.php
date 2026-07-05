<?php

declare(strict_types=1);

namespace BEAR\Skeleton\Resource\Page;

use BEAR\Resource\ResourceObject;

class Ticket extends ResourceObject
{
    public function onPost(string $title, int $qty): static
    {
        $this->body = ['title' => $title, 'qty' => $qty];

        return $this;
    }

    public function onPut(string $title, int $qty): static
    {
        $this->body = ['title' => $title, 'qty' => $qty];

        return $this;
    }
}
