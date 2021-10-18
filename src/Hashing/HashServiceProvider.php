<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;
use Swoole\Server;

class HashServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @param Server|null $server
     * @param int|null $workerId
     * @return void
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        $this->app->singleton('hash', function () {
            return new HashManager($this->app);
        });

        $this->app->singleton('hash.driver', function () {
            return $this->app['hash']->driver();
        });
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        // TODO: Implement boot() method.
    }
}
