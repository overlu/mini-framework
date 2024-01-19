<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use ArrayAccess;
use Closure;

interface Cache extends ArrayAccess
{
    /**
     * Retrieve an item from the cache by key.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Retrieve an item from the cache by key.
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed;

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param array $keys
     * @param mixed|null $default
     * @return array
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param array $keys
     * @param mixed|null $default
     * @return array
     */
    public function many(array $keys, mixed $default = null): array;

    /**
     * Store an item in the cache for the default time.
     * @param mixed $offset
     * @param mixed $value
     * @return bool
     */
    public function offsetSet(mixed $offset, mixed $value): bool;

    /**
     * Store an item in the cache.
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Store an item in the cache.
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Store an item in the cache if the key does not exist.
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function add(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     * @param string $key
     * @param Closure $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, Closure $callback, ?int $ttl = null): mixed;

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function rememberForever(string $key, Closure $callback): mixed;

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param string $key
     * @param Closure $callback
     * @return mixed
     */
    public function sear(string $key, Closure $callback): mixed;

    /**
     * Store an item in the cache indefinitely.
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever(string $key, mixed $value): bool;

    /**
     * Remove an item from the cache.
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Remove an item from the cache.
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Remove an item from the cache.
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool;

    /**
     * Remove an item from the cache.
     * @param mixed $offset
     * @return bool
     */
    public function offsetUnset(mixed $offset): bool;

    /**
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;

    public function clear(): bool;

    public function flush(): bool;

    public function has(string $key): bool;

    /**
     * Determine if a cached value exists.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool;

    /**
     * Determine if an item doesn't exist in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function missing(string $key): bool;

    /**
     * Increment the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    public function inc(string $key, int $step = 1): int;

    /**
     * Increment the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    public function increment(string $key, int $step = 1): int;

    /**
     * Decrement the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    public function dec(string $key, int $step = 1): int;

    /**
     * Decrement the value of an item in the cache.
     * @param string $key
     * @param int $step
     * @return int
     */
    public function decrement(string $key, int $step = 1): int;

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed;

    /**
     * Store multiple items in the cache for a given number of seconds.
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Store multiple items in the cache for a given number of seconds.
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public function putMany(array $values, ?int $ttl = null): bool;

    /**
     * Store multiple items in the cache indefinitely.
     * @param array $values
     * @return bool
     */
    public function putManyForever(array $values): bool;


    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void;

    /**
     * @return string
     */
    public function getPrefix(): string;
}