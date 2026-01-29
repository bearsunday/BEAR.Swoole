<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\QueryRepository\ServerContextInterface;
use Override;
use Swoole\Coroutine;
use Swoole\Http\Request;

/**
 * Server context implementation for Swoole coroutine environments
 *
 * This implementation retrieves server context values from the Swoole coroutine
 * context instead of the $_SERVER superglobal, enabling thread-safe operation
 * in concurrent request processing.
 */
final class SwooleServerContext implements ServerContextInterface
{
    #[Override]
    public function get(string $key): string|null
    {
        $request = $this->findSwooleRequest();
        if ($request === null) {
            return null;
        }

        $server = SwooleServerRequestConverter::toGlobals($request);

        return isset($server[$key]) && is_string($server[$key]) ? $server[$key] : null;
    }

    #[Override]
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Find Swoole request from coroutine context (current or parent).
     */
    private function findSwooleRequest(): Request|null
    {
        $currentCid = Coroutine::getCid();
        if (! is_int($currentCid) || $currentCid === -1) {
            return null;
        }

        /** @var int|false $cid */
        $cid = $currentCid;

        while (is_int($cid) && $cid !== -1) {
            /** @var ArrayObject<string, mixed>|null $context */
            $context = Coroutine::getContext($cid);
            if ($context === null) {
                break;
            }

            if (isset($context[Request::class]) && $context[Request::class] instanceof Request) {
                return $context[Request::class];
            }

            $cid = Coroutine::getPcid($cid);
        }

        return null;
    }
}
