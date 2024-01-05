<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Config;

use Mini\Support\Arr;

trait Repository
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->repository, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param array|string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->repository, $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param array $keys
     * @return array
     */
    public function getMany(array $keys): array
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->repository, $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param array|string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->repository, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function prepend(string $key, $value): void
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function push(string $key, $value): void
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->repository;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param string $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->set($key, null);
    }
}

