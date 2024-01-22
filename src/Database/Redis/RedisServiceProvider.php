<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Service\AbstractServiceProvider;
use RuntimeException;

class RedisServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Missing php-redis extension');
        }

        $this->app->singleton(\Mini\Contracts\Redis::class, function () {
            return new Redis();
        });
        $this->app->alias(\Mini\Contracts\Redis::class, 'redis');
    }

    public function boot(): void
    {
        //
    }
}
