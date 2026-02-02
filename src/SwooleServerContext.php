<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\QueryRepository\ServerContextInterface;
use Override;
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
        $request = CoroutineContextFinder::find(Request::class, Request::class);
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
}
