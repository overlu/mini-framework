<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->app->singleton('redis', function () {
            return new Redis();
        });
    }

    public function boot(): void
    {
        //
    }
}
