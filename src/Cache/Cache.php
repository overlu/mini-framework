<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache;

use Mini\Cache\Drivers\AbstractCacheDriver;
use Mini\Cache\Drivers\FileCacheCacheDriver;
use Mini\Cache\Drivers\RedisCacheCacheDriver;
use Mini\Cache\Drivers\SwooleCacheCacheDriver;
use Mini\Exceptions\CacheException;
use Mini\Singleton;

/**
 * 缓存管理器
 * Class Cache
 * @method static mixed get($key, $default = null)
 * @method static bool set($key, $value, $ttl = null)
 * @method static bool delete($key)
 * @method static bool clear()
 * @method static iterable getMultiple($keys, $default = null)
 * @method static bool setMultiple($values, $ttl = null)
 * @method static bool deleteMultiple($keys)
 * @method static bool has($key)
 * @package Mini\Cache
 */
class Cache
{
    use Singleton;

    protected array $mapping = [
        'file' => FileCacheCacheDriver::class,
        'redis' => RedisCacheCacheDriver::class,
        'swoole' => SwooleCacheCacheDriver::class
    ];

    /**
     * 缓存驱动
     * @var AbstractCacheDriver[]
     */
    protected array $drivers = [];

    /**
     * @var mixed
     */
    protected $default;

    private function __construct()
    {
        $driver = config('cache.default', 'file');
        $this->default = new (config('cache.drivers.' . $driver . '.driver', $this->mapping[$driver] ?? FileCacheCacheDriver::class));
    }

    /**
     * @param string $driver
     * @param string $driverName
     * @return $this
     * @throws CacheException
     */
    public function addDriver(string $driver, string $driverName = 'default'): self
    {
        if ($driverName !== 'default' && array_key_exists($driverName, $this->drivers)) {
            throw new CacheException("{$driverName} driver has been used");
        }
        if ((new $driver) instanceof AbstractCacheDriver) {
            $this->drivers[$driverName] = $driver;
        }
        return $this;
    }


    /**
     * @param string|null $driverName
     * @return AbstractCacheDriver
     */
    public function getDriver(string $driverName = null): AbstractCacheDriver
    {
        return $driverName ? new $this->drivers[$driverName] : $this->default;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::getInstance()->getDriver()->$name(...$arguments);
    }
}