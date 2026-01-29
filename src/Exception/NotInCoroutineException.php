<?php

declare(strict_types=1);

namespace BEAR\Swoole\Exception;

use RuntimeException;

final class NotInCoroutineException extends RuntimeException
{
}
