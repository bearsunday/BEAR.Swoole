<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Psr\Http\Message\UploadedFileInterface;
use Ray\Di\ProviderInterface;

/**
 * @implements ProviderInterface<array<UploadedFileInterface>>
 * @codeCoverageIgnore Swoole coroutine context only
 */
final readonly class SwooleUploadfilesProvider implements ProviderInterface
{
    public function __construct(
        private SwooleRequestProvider $requestProvider,
    ) {
    }

    /** @return array<UploadedFileInterface> */
    public function get(): array
    {
        return $this->requestProvider->get()->getUploadedFiles();
    }
}
