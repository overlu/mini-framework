<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use Mini\Database\Redis\Pool;
use Redis;
use Swoole\Coroutine;

class RedisCacheCacheDriver extends AbstractCacheDriver
{
    private Redis $connection;

    public function __construct()
    {
        $this->prefix = config('cache.prefix', '');
        $this->connection = Pool::getInstance()->getConnection(config('cache.drivers.redis.collection', 'default'));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->prefix . $key;
        $value = (is_array($value) || is_object($value)) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        return $ttl
            ? $this->connection->setex($this->prefix . $key, (int)$ttl, $value)
            : $this->connection->set($this->prefix . $key, $value);
    }

    /**
     * @param string $key
     * @param null $default
     * @return bool|mixed|string|null
     */
    public function get($key, $default = null)
    {
        $value = $this->connection->get($this->prefix . $key);
        return $value === false ? $default : (is_json($value) ? json_decode($value, true) : $value);
    }

    /**
     * @param $key
     * @param int $step
     * @return int
     */
    public function inc($key, int $step = 1): int
    {
        return $this->connection->incrBy($this->prefix . $key, $step);
    }

    /**
     * @param $key
     * @param int $step
     * @return int
     */
    public function dec($key, int $step = 1): int
    {
        return $this->connection->decrby($this->prefix . $key, $step);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return $this->connection->exists($this->prefix . $key);
    }

    /**
     * @param string $key
     * @return bool|int
     */
    public function delete($key)
    {
        return $this->connection->del($this->prefix . $key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return $this->connection->flushDB();
    }

    protected function isPoolConnection(): bool
    {
        return Coroutine::getCid() > 0;
    }
}