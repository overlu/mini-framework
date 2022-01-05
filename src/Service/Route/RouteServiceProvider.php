<?php
/**
 * This file is part of zhishuo.
 * @auth lupeng
 * @date 2022/1/5 10:59
 */
declare(strict_types=1);

namespace Mini\Service\Route;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton('route', function () {
            $route = new Route();
            $route->initRoutes();
            return $route;
        });
        $this->app->singleton('url', function () {
            return new UrlGenerator();
        });
    }

    public function boot(): void
    {
        //
    }
}