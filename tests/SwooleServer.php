<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use RuntimeException;
use Symfony\Component\Process\Process;

use function error_log;
use function register_shutdown_function;
use function sprintf;
use function str_contains;

use const PHP_BINARY;

final class SwooleServer
{
    /** @var Process<mixed> */
    private Process $process;

    public function __construct(string $phpFile)
    {
        $this->process = new Process([
            PHP_BINARY,
            $phpFile,
        ]);
        register_shutdown_function(function (): void {
            $this->process->stop();
        });
    }

    public function start(): void
    {
        $this->process->start();
        $this->process->waitUntil(function (string $type, string $output): bool {
            if ($type === 'err') {
                error_log($output);
            }

            return str_contains($output, 'started');
        });
    }

    public function stop(): void
    {
        $exitCode = $this->process->stop();
        if ($exitCode !== 143) {
            throw new RuntimeException(sprintf('code:%s msg:%s', (string) $exitCode, $this->process->getErrorOutput()));
        }
    }
}
