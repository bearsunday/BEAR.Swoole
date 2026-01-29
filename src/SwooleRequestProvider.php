<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\Swoole\Exception\NotInCoroutineException;
use Psr\Http\Message\ServerRequestInterface;
use Ray\Di\ProviderInterface;
use Ray\HttpMessage\RequestProviderInterface;
use Swoole\Coroutine;
use Swoole\Http\Request;

/**
 * @implements ProviderInterface<ServerRequestInterface>
 * @psalm-suppress DeprecatedInterface
 */
final class SwooleRequestProvider implements ProviderInterface, RequestProviderInterface
{
    private ?SwooleRequestProxy $proxy = null;

    public function __construct(
        private readonly SwooleServerRequestConverter $converter,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function get(): ServerRequestInterface
    {
        return $this->proxy ??= new SwooleRequestProxy($this->converter);
    }

    /**
     * Seed the context with the raw Swoole request and return the standardized $_SERVER array.
     *
     * @return array<string, mixed>
     */
    public static function seed(Request $request): array
    {
        /** @var ArrayObject<string, mixed>|null $context */
        $context = Coroutine::getContext();
        if ($context === null) {
            throw new NotInCoroutineException(); // @codeCoverageIgnore
        }

        $server = SwooleServerRequestConverter::toGlobals($request);

        $context[Request::class] = $request;
        $context['$_SERVER'] = $server;

        return $server;
    }
}
