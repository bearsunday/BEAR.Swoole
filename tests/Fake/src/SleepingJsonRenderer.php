<?php

declare(strict_types=1);

namespace BEAR\SwooleFake;

use BEAR\Resource\JsonRenderer;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Swoole\Coroutine;

/**
 * Delegates to the default JSON rendering but yields the coroutine first, standing in for
 * any I/O-bound renderer (templates, remote calls) under SWOOLE_HOOK_ALL.
 */
final readonly class SleepingJsonRenderer implements RenderInterface
{
    public function __construct(private float $seconds)
    {
    }

    #[Override]
    public function render(ResourceObject $ro): string
    {
        Coroutine::sleep($this->seconds);

        return (new JsonRenderer())->render($ro);
    }
}
