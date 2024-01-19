<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

trait Container
{
    /**
     * @var array
     */
    protected static array $container = [];

    /**
     * @param string $id
     * @param $value
     */
    public static function set(string $id, $value): void
    {
        static::$container[$id] = $value;
    }

    /**
     * @param string $id
     * @param null $default
     * @return mixed|null
     */
    public static function get(string $id, $default = null): mixed
    {
        return static::$container[$id] ?? $default;
    }

    /**
     * @param string $id
     * @return bool
     */
    public static function has(string $id): bool
    {
        return isset(static::$container[$id]);
    }

    public static function list(): array
    {
        return static::$container;
    }
}
