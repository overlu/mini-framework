<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Swoole\Coroutine as SwooleCoroutine;
use Throwable;

/**
 * @method static void defer(callable $callable)
 */
class Coroutine
{
    public static function __callStatic($name, $arguments)
    {
        if (!method_exists(SwooleCoroutine::class, $name)) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s.', $name));
        }
        return SwooleCoroutine::$name(...$arguments);
    }

    /**
     * Returns the current coroutine ID.
     * Returns -1 when running in non-coroutine context.
     */
    public static function id(): int
    {
        return SwooleCoroutine::getCid();
    }

    /**
     * Returns the parent coroutine ID.
     * Returns -1 when running in the top level coroutine.
     * Returns null when running in non-coroutine context.
     *
     * @see https://github.com/swoole/swoole-src/pull/2669/files#diff-3bdf726b0ac53be7e274b60d59e6ec80R940
     */
    public static function parentId(): ?int
    {
        $cid = SwooleCoroutine::getPcid();
        if ($cid === false) {
            return null;
        }

        return $cid;
    }

    /**
     * @param callable $callable
     * @return int Returns the coroutine ID of the coroutine just created.
     *             Returns -1 when coroutine create failed.
     */
    public static function create(callable $callable): int
    {
        $result = SwooleCoroutine::create(static function () use ($callable) {
            try {
                call($callable);
            } catch (Throwable $throwable) {
                app('exception')->throw($throwable);
            }
        });
        return is_int($result) ? $result : -1;
    }

    public static function inCoroutine(): bool
    {
        return self::id() > 0;
    }
}
