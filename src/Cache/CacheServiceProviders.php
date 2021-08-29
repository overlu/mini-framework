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
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

/**
 * Class CacheServiceProviders
 * @package Mini\Cache
 */
class CacheServiceProviders implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        //
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerFileCacheDriver(): void
    {
        app()->singleton('cache.driver.file', function () {
            return new FileCacheCacheDriver();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerSwooleCacheDriver(): void
    {
        app()->singleton('cache.driver.swoole', function () {
            return new SwooleCacheCacheDriver();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerRedisCacheDriver(): void
    {
        app()->singleton('cache.driver.redis', function () {
            return new RedisCacheCacheDriver();
        });
    }

    /**
     * @throws BindingResolutionException
     */
    private function registerCache(): void
    {
        app()->singleton('cache', function () {
            return new Cache();
        });
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        $this->registerFileCacheDriver();
        $this->registerRedisCacheDriver();
        $this->registerSwooleCacheDriver();
        $this->registerCache();
    }
}