<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use ArrayAccess;
use Mini\Service\HttpServer\RouteService;
use ReflectionException;
use Throwable;

/**
 * Class Route
 * @package Mini\Facades
 */
class Route
{
    /**
     * 注册http服务路由
     * @param array $route
     */
    public static function registerHttpRoute(array $route): void
    {
        RouteService::registerHttpRoute($route);
    }

    /**
     * 注册websocket服务路由
     * @param array $route
     */
    public static function registerWsRoute(array $route): void
    {
        RouteService::registerWsRoute($route);
    }

    /**
     * @param $handler
     * @param array $params
     * @param string $uri
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public static function dispatchHandle($handler, array $params = [], string $uri = '')
    {
        return RouteService::getInstance()->dispatchHandle($handler, $params, $uri);
    }

    /**
     * 获取所有的已注册路由
     * @return array
     */
    public static function routes(): array
    {
        return RouteService::getInstance()->routes();
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return RouteService::getInstance()->hasRoute($key);
    }

    /**
     * 获取所有的已注册http服务路由
     * @return array
     */
    public static function httpRoutes(): array
    {
        return RouteService::getInstance()->httpRoutes();
    }

    /**
     * 获取所有的已注册websocket服务路由
     * @return array
     */
    public static function wsRoutes(): array
    {
        return RouteService::getInstance()->wsRoutes();
    }

    /**
     * 获取http路由
     * @param string $key
     * @param null $default
     * @return array|ArrayAccess|mixed
     */
    public static function httpRoute(string $key, $default = null)
    {
        return RouteService::getInstance()->httpRoute($key, $default);
    }

    /**
     * 获取ws路由
     * @param string $key
     * @param null $default
     * @return array|ArrayAccess|mixed
     */
    public static function wsRoute(string $key, $default = null)
    {
        return RouteService::getInstance()->wsRoute($key, $default);
    }

    /**
     * 获取路由
     * @param string $key
     * @param null $default
     * @return array|ArrayAccess|mixed
     */
    public static function route(string $key, $default = null)
    {
        return RouteService::getInstance()->route($key, $default);
    }
}
