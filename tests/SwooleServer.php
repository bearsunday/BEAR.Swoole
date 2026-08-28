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

    /**
     * The port the suite binds and requests.
     *
     * Overridable because an unrelated process holding 8088 would otherwise stop the suite from
     * running at all: BEAR_SWOOLE_TEST_PORT=18788 composer test
     */
    public static function port(): int
    {
        $port = getenv('BEAR_SWOOLE_TEST_PORT');

        return $port === false ? 8088 : (int) $port;
    }

    public function __construct(string $phpFile, private readonly int $port)
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
        // Without this the suite would silently exercise whatever else holds the port: our
        // server fails to bind, waitUntil() returns on its exit, and the requests still get
        // answers - from a different application.
        $occupant = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.5);
        if (is_resource($occupant)) {
            fclose($occupant);

            throw new RuntimeException(sprintf('Port %d is already in use', $this->port));
        }

        $this->process->start();
        $started = $this->process->waitUntil(function (string $type, string $output): bool {
            if ($type === 'err') {
                error_log($output);
            }

            return str_contains($output, 'started');
        });
        if ($started) {
            return;
        }

        throw new RuntimeException(sprintf(
            "Server exited before reporting readiness:\n%s%s",
            $this->process->getOutput(),
            $this->process->getErrorOutput(),
        ));
    }

    public function stop(): void
    {
        $exitCode = $this->process->stop();
        if ($exitCode !== 143) {
            throw new RuntimeException(sprintf('code:%s msg:%s', (string) $exitCode, $this->process->getErrorOutput()));
        }
    }
}
