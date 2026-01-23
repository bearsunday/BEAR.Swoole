<?php

declare(strict_types=1);

namespace BEAR\Skeleton\Resource\Page;

use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\ResourceObject;

#[Cacheable]
class Cache extends ResourceObject
{
    public function onGet(string $name = 'BEAR.Sunday'): static
    {
        $this->body = [
            'greeting' => 'Hello ' . $name,
        ];

        return $this;
    }
}
