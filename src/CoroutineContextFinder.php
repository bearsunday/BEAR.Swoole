<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use Swoole\Coroutine;

/**
 * Utility to find a value from Swoole coroutine context
 *
 * Traverses the current coroutine and its parent coroutines to find
 * a stored value by key and type. This eliminates duplicated parent
 * coroutine traversal logic across multiple classes.
 */
final class CoroutineContextFinder
{
    /**
     * Find a value in the coroutine context hierarchy.
     *
     * Walks from the current coroutine up through parent coroutines,
     * returning the first value that matches the given key and type.
     *
     * @template T of object
     *
     * @param class-string<T> $key  Context key (typically an FQCN)
     * @param class-string<T> $type Expected type of the value
     *
     * @return T|null
     */
    public static function find(string $key, string $type): object|null
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

            if (isset($context[$key]) && $context[$key] instanceof $type) {
                /** @var T */
                return $context[$key];
            }

            $cid = Coroutine::getPcid($cid);
        }

        return null;
    }
}
