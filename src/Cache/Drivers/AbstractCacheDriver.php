<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

/**
 * 抽象驱动类
 * Class AbstractDriver
 * @package Mini\Cache\Drivers
 */
abstract class AbstractCacheDriver
{
    protected ?string $prefix = '';

    abstract public function get(string $key, $default = null);

    abstract public function set(string $key, $value, ?int $ttl = null): bool;

    abstract public function delete(string $key): bool;

    abstract public function clear(): bool;

    abstract public function has(string $key): bool;

    abstract public function inc(string $key, int $step = 1): int;

    abstract public function dec(string $key, int $step = 1): int;

    /**
     * @param iterable $keys
     * @param null $default
     * @return array|iterable
     */
    public function getMultiple(iterable $keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @param iterable $values
     * @param int|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, ?int $ttl = null): bool
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
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);
            if (false === $result) {
                return false;
            }
        }
        return true;
    }


    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return mixed|null
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }
}