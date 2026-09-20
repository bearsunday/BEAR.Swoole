<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use BEAR\Swoole\Exception\NotInCoroutineException;
use BEAR\Swoole\Exception\ResponseNotSeededException;
use Override;
use Swoole\Coroutine;
use Swoole\Http\Response;

/**
 * Each worker builds exactly one Responder (via the singleton App graph), so a response
 * stashed in an instance field would be shared by every coroutine that worker is running
 * concurrently: a yield between seed() and end() (e.g. a renderer doing hooked I/O) would
 * let a second request overwrite the field before the first finishes writing to it, sending
 * one client's body to another's connection. The Swoole response therefore travels through
 * coroutine-local context instead, the same mechanism used for the request in
 * SwooleRequestProvider::seed().
 *
 * @codeCoverageIgnore Swoole server context only
 */
final class Responder implements TransferInterface
{
    #[Override]
    public function __invoke(ResourceObject $ro, array $server): void
    {
        unset($server);
        $response = CoroutineContextFinder::find(Response::class, Response::class);
        if ($response === null) {
            throw new ResponseNotSeededException();
        }

        $ro->toString();
        foreach ($ro->headers as $key => $value) {
            $response->header($key, (string) $value);
        }

        $response->status($ro->code);
        $response->end($ro->view);
    }

    /**
     * Seed the current coroutine's context with the Swoole response to write to.
     */
    public static function seed(Response $response): void
    {
        /** @var ArrayObject<string, mixed>|null $context */
        $context = Coroutine::getContext();
        if ($context === null) {
            throw new NotInCoroutineException();
        }

        $context[Response::class] = $response;
    }
}
