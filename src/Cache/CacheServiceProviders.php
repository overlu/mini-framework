<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Cache;

use Mini\Cache\Drivers\FileCacheCacheDriver;
use Mini\Cache\Drivers\RedisCacheCacheDriver;
use Mini\Cache\Drivers\SwooleCacheCacheDriver;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;
use ReflectionException;

/**
 * Class CacheServiceProviders
 * @package Mini\Cache
 */
class CacheServiceProviders extends AbstractServiceProvider
{
    public function register(): void
    {
        $drivers = array_keys(config('cache.drivers', []));
        in_array('file', $drivers, true) && $this->registerFileCacheDriver();
        in_array('redis', $drivers, true) && $this->registerRedisCacheDriver();
        in_array('swoole', $drivers, true) && $this->registerSwooleCacheDriver();
        $this->registerCache();
    }

    private function registerFileCacheDriver(): void
    {
        $this->app->singleton('cache.driver.file', function () {
            return new FileCacheCacheDriver();
        });
    }

    private function registerSwooleCacheDriver(): void
    {
        $this->app->singleton('cache.driver.swoole', function () {
            return new SwooleCacheCacheDriver();
        });
    }

    private function registerRedisCacheDriver(): void
    {
        $this->app->singleton('cache.driver.redis', function () {
            return new RedisCacheCacheDriver();
        });
    }

    private function registerCache(): void
    {
        $this->app->singleton(\Mini\Cache\Cache::class, function () {
            return new Cache();
        });
        $this->app->alias(\Mini\Cache\Cache::class, 'cache');
        $this->app->singleton(\Mini\Contracts\Cache::class, function () {
            return $this->app['cache.driver.' . config('cache.default', 'file')];
        });
    }

    public function boot(): void
    {
    }
}