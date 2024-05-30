<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use Closure;
use Mini\Contracts\Cache;

/**
 * 抽象驱动类
 * Class AbstractDriver
 * @package Mini\Cache\Drivers
 */
abstract class AbstractCacheDriver implements Cache
{
    protected string $prefix = '';

    /**
     * Retrieve an item from the cache by key.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieve an item from the cache by key.
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param array $keys
     * @param mixed|null $default
     * @return array
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param array $keys
     * @param mixed|null $default
     * @return array
     */
    public function many(array $keys, mixed $default = null): array
    {
        return $this->getMultiple($keys, $default);
    }

    /**
     * Store an item in the cache for the default time.
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->put($offset, $value, 3600);
    }

    /**
     * Store an item in the cache.
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    abstract public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Store an item in the cache.
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->set($key, $value, $ttl);
    }

    /**
     * Store an item in the cache if the key does not exist.
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl <= 0 && $ttl !== null) {
            return false;
        }
        if (is_null($this->get($key))) {
            return $this->put($key, $value, $ttl);
        }
        return false;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     * @param mixed $key
     * @param Closure $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);
        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of seconds so it's available for all subsequent requests.
        if (!is_null($value)) {
            return $value;
        }

        $this->set($key, $value = $callback(), $ttl);
        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, Closure $callback): mixed
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately
        // and if not we will execute the given Closure and cache the result
        // of that forever so it is available for all subsequent requests.
        if (!is_null($value)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function sear(string $key, Closure $callback): mixed
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Store an item in the cache indefinitely.
     * @param string $key
     * @param $value
     * @return bool
     */
    public function forever(string $key, $value): bool
    {
        return $this->set($key, $value);
    }

    /**
     * Remove an item from the cache.
     * @param string $key
     * @return bool
     */
    abstract public function delete(string $key): bool;

    /**
     * Remove an item from the cache.
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    /**
     * Remove an item from the cache.
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool
    {
        return $this->delete($key);
    }

    /**
     * Remove an item from the cache.
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->delete($offset);
    }

    /**
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);
            if (false === $result) {
                return false;
            }
        }
        return true;
    }

    abstract public function clear(): bool;

    public function flush(): bool
    {
        return $this->clear();
    }

    abstract public function has(string $key): bool;

    /**
     * Determine if a cached value exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Determine if an item doesn't exist in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Increment the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    abstract public function inc(string $key, int $step = 1): int;

    /**
     * Increment the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    public function increment(string $key, int $step = 1): int
    {
        return $this->inc($key, $step);
    }

    /**
     * Decrement the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    abstract public function dec(string $key, int $step = 1): int;

    /**
     * Decrement the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    public function decrement(string $key, int $step = 1): int
    {
        return $this->dec($key, $step);
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return tap($this->get($key, $default), function () use ($key) {
            $this->delete($key);
        });
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        foreach ($values as $key => $val) {
            $result = $this->set($key, $val, $ttl);
            if (false === $result) {
                return false;
            }
        }
        return true;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function putMany(array $values, ?int $ttl = null): bool
    {
        return $this->setMultiple($values, $ttl);
    }

    /**
     * Store multiple items in the cache indefinitely.
     * @param array $values
     * @return bool
     */
    public function putManyForever(array $values): bool
    {
        return $this->setMultiple($values);
    }


    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}