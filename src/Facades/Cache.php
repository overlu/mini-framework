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
 * @method static mixed get($key, $default = null)
 * @method static bool set($key, $value, $ttl = null)
 * @method static bool delete($key)
 * @method static bool clear()
 * @method static iterable getMultiple($keys, $default = null)
 * @method static bool setMultiple($values, $ttl = null)
 * @method static bool deleteMultiple($keys)
 * @method static bool has($key)
 * @method static AbstractCacheDriver driver($driverName)
 * @package Mini\Facades
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Mini\Cache\Cache::getInstance();
    }
}