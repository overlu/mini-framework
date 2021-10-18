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
use Mini\Support\ServiceProvider;

/**
 * Class CacheServiceProviders
 * @package Mini\Cache
 */
class CacheServiceProviders extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerFileCacheDriver(): void
    {
        $this->app->singleton('cache.driver.file', function () {
            return new FileCacheCacheDriver();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerSwooleCacheDriver(): void
    {
        $this->app->singleton('cache.driver.swoole', function () {
            return new SwooleCacheCacheDriver();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerRedisCacheDriver(): void
    {
        $this->app->singleton('cache.driver.redis', function () {
            return new RedisCacheCacheDriver();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerCache(): void
    {
        $this->app->singleton('cache', function () {
            return new Cache();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->registerFileCacheDriver();
        $this->registerRedisCacheDriver();
        $this->registerSwooleCacheDriver();
        $this->registerCache();
    }
}