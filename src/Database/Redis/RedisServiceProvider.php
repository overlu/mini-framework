<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;
use ReflectionException;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function register(): void
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('Missing php-redis extension');
        }
        $this->app->singleton('redis', function () {
            return new Redis();
        });
    }

    public function boot(): void
    {
        //
    }
}
