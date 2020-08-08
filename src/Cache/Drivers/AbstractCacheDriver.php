<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache\Drivers;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * 抽象驱动类
 * Class AbstractDriver
 * @package Mini\Cache\Drivers
 */
abstract class AbstractCacheDriver implements CacheInterface
{
    protected ?string $prefix = '';

    abstract public function get($key, $default = null);

    abstract public function set($key, $value, $ttl = null);

    abstract public function delete($key);

    abstract public function clear();

    abstract public function has($key);

    abstract public function inc($key, int $step = 1);

    abstract public function dec($key, int $step = 1);

    /**
     * @param iterable $keys
     * @param null $default
     * @return array|iterable
     * @throws InvalidArgumentException
     */
    public function getMultiple($keys, $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null): bool
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
     * @throws InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
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