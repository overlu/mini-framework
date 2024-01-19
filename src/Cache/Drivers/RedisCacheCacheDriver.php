<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

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
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl <= 0 && !is_null($ttl)) {
            return $this->delete($key);
        }
        return $ttl
            ? (bool)Redis::connection($this->connection)->setex($this->prefix . $key, $ttl, serialize($value))
            : (bool)Redis::connection($this->connection)->set($this->prefix . $key, serialize($value));
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Redis::connection($this->connection)->get($this->prefix . $key);
        return $value === false ? $default : unserialize($value, ["allowed_classes" => true]);
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     */
    public function inc(string $key, int $step = 1): int
    {
        return (int)Redis::connection($this->connection)->incrBy($this->prefix . $key, $step);
    }

    /**
     * @param string $key
     * @param int $step
     * @return int
     */
    public function dec(string $key, int $step = 1): int
    {
        return (int)Redis::connection($this->connection)->decrby($this->prefix . $key, $step);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return (bool)Redis::connection($this->connection)->exists($this->prefix . $key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return (bool)Redis::connection($this->connection)->unlink($this->prefix . $key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return Redis::connection($this->connection)->flushDB();
    }
}
