<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use function error_log;
use function register_shutdown_function;
use RuntimeException;
use function sprintf;
use function strpos;
use Symfony\Component\Process\Process;
use function var_dump;

final class SwooleServer
{
    /**
     * @var Process<mixed>
     */
    private $process;

    /**
     * @var string
     */
    private $host;

    public function __construct(string $phpFile)
    {
        $this->process = new Process([
            PHP_BINARY,
            $phpFile
        ]);
        register_shutdown_function(function () {
            $this->process->stop();
        });
    }

    public function start() : void
    {
        $this->process->start();
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            sleep(1);

            return;
        }
        $this->process->waitUntil(function (string $type, string $output) : bool {
            if ($type === 'err') {
                error_log($output);
            }
            return (bool) strpos($output, 'started');
        });
    }

    public function stop() : void
    {
        $exitCode = $this->process->stop();
        if ($exitCode !== 143) {
            throw new RuntimeException(sprintf('code:%s msg:%s', (string) $exitCode, $this->process->getErrorOutput()));
        }
    }
}
