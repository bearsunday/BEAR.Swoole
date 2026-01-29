<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\Resource\ParamInterface;
use Override;
use Ray\Di\InjectorInterface;
use Ray\WebContextParam\Annotation\AbstractWebContextParam;
use Swoole\Coroutine;
use Swoole\Http\Request;

/**
 * Provides web context parameters from Swoole coroutine context.
 *
 * This class replaces AssistedWebContextParam for Swoole environments,
 * reading request data from coroutine context instead of $GLOBALS.
 *
 * @psalm-import-type Query from \BEAR\Resource\Types
 */
final class SwooleAssistedWebContextParam implements ParamInterface
{
    public function __construct(
        private readonly AbstractWebContextParam $webContextParam,
        private readonly ParamInterface $defaultParam,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-taint-source input
     */
    #[Override]
    public function __invoke(string $varName, array $query, InjectorInterface $injector): mixed
    {
        $webContext = $this->getWebContextFromCoroutine();
        $key = $this->webContextParam->key;

        return $webContext[$key] ?? ($this->defaultParam)($varName, $query, $injector);
    }

    /**
     * Get web context data from Swoole coroutine context.
     *
     * Maps GLOBAL_KEY to Swoole request properties:
     * - _GET    → $request->get
     * - _POST   → $request->post
     * - _COOKIE → $request->cookie
     * - _SERVER → toGlobals($request)
     * - _FILES  → $request->files
     *
     * @return array<string, mixed>
     */
    private function getWebContextFromCoroutine(): array
    {
        $swooleRequest = $this->findSwooleRequest();
        if ($swooleRequest === null) {
            return [];
        }

        $globalKey = $this->webContextParam::GLOBAL_KEY;

        return match ($globalKey) {
            '_GET' => $swooleRequest->get ?? [],
            '_POST' => $swooleRequest->post ?? [],
            '_COOKIE' => $swooleRequest->cookie ?? [],
            '_SERVER' => SwooleServerRequestConverter::toGlobals($swooleRequest),
            '_FILES' => $swooleRequest->files ?? [],
            default => [],
        };
    }

    /**
     * Find Swoole request from coroutine context (current or parent).
     */
    private function findSwooleRequest(): ?Request
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
