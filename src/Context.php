<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use ArrayObject;
use Mini\Support\Arr;
use Swoole\Coroutine;

class Context
{
    protected static array $nonCoContext = [];

    public static function set(string $id, $value)
    {
        if (self::inCoroutine()) {
            Coroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (self::inCoroutine()) {
            if ($coroutineId !== null) {
                return Coroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return Coroutine::getContext()[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    public static function has(string $id, $coroutineId = null): bool
    {
        if (self::inCoroutine()) {
            if ($coroutineId !== null) {
                return isset(Coroutine::getContext($coroutineId)[$id]);
            }
            return isset(Coroutine::getContext()[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * Copy the context from a coroutine to current coroutine.
     * @param int $fromCoroutineId
     * @param array $keys
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void
    {
        /**
         * @var ArrayObject
         * @var ArrayObject $current
         */
        $from = Coroutine::getContext($fromCoroutineId);
        $current = Coroutine::getContext();
        $current->exchangeArray($keys ? Arr::only($from->getArrayCopy(), $keys) : $from->getArrayCopy());
    }

    /**
     * Release the context when you are not in coroutine environment.
     * @param string $id
     */
    public static function destroy(string $id): void
    {
        unset(static::$nonCoContext[$id]);
    }

    /**
     * Retrieve the value and override it by closure.
     * @param string $id
     * @param \Closure $closure
     * @return mixed
     */
    public static function override(string $id, \Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }

    public static function inCoroutine(): bool
    {
        return Coroutine::getCid() > 0;
    }

    /**
     * Retrieve the value and store it if not exists.
     * @param string $id
     * @param mixed $value
     * @return mixed|null
     */
    public static function getOrSet(string $id, $value)
    {
        if (!self::has($id)) {
            return self::set($id, value($value));
        }
        return self::get($id);
    }

    public static function getContainer()
    {
        if (self::inCoroutine()) {
            return Coroutine::getContext();
        }

        return static::$nonCoContext;
    }

    public static function pull(string $id, $default = null)
    {
        $result = static::get($id, $default);
        static::destroy($id);
        return $result;
    }
}
