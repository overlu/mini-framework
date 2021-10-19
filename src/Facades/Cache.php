<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Cache\Drivers\AbstractCacheDriver;

/**
 * Class Cache
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static mixed offsetGet(string $key)
 * @method static iterable getMultiple(array $keys, mixed $default = null)
 * @method static iterable many(array $keys, mixed $default = null)
 * @method static bool set(string $key, $value, ?int $ttl = null)
 * @method static bool put(string $key, $value, ?int $ttl = null)
 * @method static bool setMultiple(array $values, ?int $ttl = null)
 * @method static bool putMany(array $values, ?int $ttl = null)
 * @method static bool putManyForever(array $values)
 * @method static bool offsetSet(string $key, $value)
 * @method static bool add(string $key, $value, ?int $ttl = null)
 * @method static bool remember(string $key, \Closure $callback, ?int $ttl = null)
 * @method static bool rememberForever(string $key, \Closure $callback)
 * @method static bool sear(string $key, \Closure $callback)
 * @method static bool forever(string $key, $value)
 * @method static bool delete(string $key)
 * @method static bool forget(string $key)
 * @method static bool offsetUnset(string $key)
 * @method static bool deleteMultiple(array $keys)
 * @method static bool clear()
 * @method static bool flush()
 * @method static bool has(string $key)
 * @method static bool offsetExists(string $key)
 * @method static bool missing(string $key)
 * @method static mixed inc(string $key, int $step = 1)
 * @method static mixed increment(string $key, int $step = 1)
 * @method static mixed dec(string $key, int $step = 1)
 * @method static mixed decrement(string $key, int $step = 1)
 * @method static void setPrefix(string $prefix)
 * @method static string getPrefix()
 * @method static AbstractCacheDriver driver(?string $driverName)
 * @package Mini\Facades
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cache';
    }
}