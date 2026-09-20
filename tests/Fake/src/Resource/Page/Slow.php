<?php

declare(strict_types=1);

namespace BEAR\SwooleFake\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\SwooleFake\SleepingJsonRenderer;

/**
 * Renders slowly (yielding the coroutine) when sleep > 0, so a concurrency test can force
 * this request's coroutine to be suspended mid-response while a sibling request runs.
 */
class Slow extends ResourceObject
{
    /** @var array{id: string} */
    public $body;

    public function onGet(string $id = '', float $sleep = 0.0): static
    {
        $this->body = ['id' => $id];
        if ($sleep > 0.0) {
            $this->setRenderer(new SleepingJsonRenderer($sleep));
        }

        return $this;
    }
}
