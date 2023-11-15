<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * @method static void registerHttpRoute(array $route)
 * @method static void registerWsRoute(array $route)
 * @method static void initRoutes()
 * @method static bool hasRoute(string $key)
 * @method static array routes()
 * @method static array httpRoutes()
 * @method static array wsRoutes()
 *
 * @see \Mini\Service\Route\Route
 */
class Route extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'route';
    }
}
