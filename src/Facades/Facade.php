<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use RuntimeException;

class Facade
{
    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static array $resolvedInstance = [];

    /**
     * Resolve the facade root instance from the container.
     *
     * @param object|string $name
     * @return mixed
     */
    protected static function resolveFacadeInstance(object|string $name): mixed
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (app()) {
            return static::$resolvedInstance[$name] = app($name);
        }

        return null;
    }

    /**
     * Get the registered name of the component.
     * @return mixed
     */
    protected static function getFacadeAccessor(): mixed
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot(): mixed
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws RuntimeException
     */
    public static function __callStatic(string $method, array $args)
    {
        $instance = static::getFacadeRoot();

        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}