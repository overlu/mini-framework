<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Database\Redis\Pool;
use Mini\Facades\Redis;

class RedisCacheCacheDriver extends AbstractCacheDriver
{
    private string $connection;

    public function __construct()
    {
        $this->prefix = config('cache.prefix', '');
        $this->connection = config('cache.drivers.redis.collection', 'default');
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     * @throws BindingResolutionException
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if ($ttl <= 0 && !is_null($ttl)) {
            return $this->delete($key);
        }
        return $ttl
            ? Redis::connection($this->connection)->setex($this->prefix . $key, $ttl, serialize($value))
            : Redis::connection($this->connection)->set($this->prefix . $key, serialize($value));
    }

    /**
     * @param string $key
     * @param null $default
     * @return bool|mixed|string|null
     * @throws BindingResolutionException
     */
    public function get(string $key, $default = null)
    {
        $value = Redis::connection($this->connection)->get($this->prefix . $key);
        return $value === false ? $default : unserialize($value);
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     * @throws BindingResolutionException
     */
    public function inc(string $key, int $step = 1): int
    {
        return Redis::connection($this->connection)->incrBy($this->prefix . $key, $step);
    }

    /**
     * @param $key
     * @param int $step
     * @return int
     * @throws BindingResolutionException
     */
    public function dec(string $key, int $step = 1): int
    {
        return Redis::connection($this->connection)->decrby($this->prefix . $key, $step);
    }

    /**
     * @param string $key
     * @return bool
     * @throws BindingResolutionException
     */
    public function has(string $key): bool
    {
        return Redis::connection($this->connection)->exists($this->prefix . $key);
    }

    /**
     * @param string $key
     * @return bool|int
     * @throws BindingResolutionException
     */
    public function delete(string $key): bool
    {
        return Redis::connection($this->connection)->del($this->prefix . $key) ? true : false;
    }

    /**
     * @return bool
     * @throws BindingResolutionException
     */
    public function clear(): bool
    {
        return Redis::connection($this->connection)->flushDB();
    }
}