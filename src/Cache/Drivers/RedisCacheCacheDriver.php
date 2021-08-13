<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use Mini\Database\Redis\Pool;
use Redis;

class RedisCacheCacheDriver extends AbstractCacheDriver
{
    private Redis $connection;

    public function __construct()
    {
        $this->prefix = config('cache.prefix', '');
        $this->connection = app('redis')->getConnection(config('cache.drivers.redis.collection', 'default'));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        return $ttl
            ? $this->connection->setex($this->prefix . $key, $ttl, serialize($value))
            : $this->connection->set($this->prefix . $key, serialize($value));
    }

    /**
     * @param string $key
     * @param null $default
     * @return bool|mixed|string|null
     */
    public function get(string $key, $default = null)
    {
        $value = $this->connection->get($this->prefix . $key);
        return $value === false ? $default : unserialize($value);
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     */
    public function inc(string $key, int $step = 1): int
    {
        return $this->connection->incrBy($this->prefix . $key, $step);
    }

    /**
     * @param $key
     * @param int $step
     * @return int
     */
    public function dec(string $key, int $step = 1): int
    {
        return $this->connection->decrby($this->prefix . $key, $step);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->connection->exists($this->prefix . $key);
    }

    /**
     * @param string $key
     * @return bool|int
     */
    public function delete(string $key): bool
    {
        return $this->connection->del($this->prefix . $key) ? true : false;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return $this->connection->flushDB();
    }
}