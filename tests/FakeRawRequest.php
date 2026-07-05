<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Swoole\Http\Request;

/**
 * Swoole request double whose raw body can be controlled in-process.
 *
 * Swoole's native rawContent() always returns false on a hand-built
 * Request, so this override lets unit tests exercise toGlobals() body
 * handling without a running server.
 */
final class FakeRawRequest extends Request
{
    public string|false $rawBody = false;

    public function rawContent(): string|false
    {
        return $this->rawBody;
    }
}
