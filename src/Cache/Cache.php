<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache;

use Mini\Cache\Drivers\AbstractCacheDriver;
use Mini\Exceptions\CacheException;

/**
 * 缓存管理器
 * Class Cache
 * @package Mini\Cache
 */
class Cache
{
    /**
     * 缓存驱动
     * @var array
     */
    protected array $drivers = ['file', 'redis', 'swoole'];

    /**
     * @var mixed
     */
    protected $default;

    public function __construct()
    {
        $this->default = config('cache.default', 'file');
    }

    /**
     * @param string $driverName
     * @return AbstractCacheDriver
     * @throws CacheException
     */
    public function driver(?string $driverName = null): AbstractCacheDriver
    {
        if (!in_array($driverName, $this->drivers, true)) {
            throw new CacheException("{$driverName} not exists.");
        }
        return app('cache.driver.' . $driverName ?: $this->default);
    }
}