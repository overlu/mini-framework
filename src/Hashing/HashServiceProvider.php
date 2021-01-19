<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class HashServiceProvider implements ServiceProviderInterface
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
        $app = app();
        $app->singleton('hash', function ($app) {
            return new HashManager($app);
        });

        $app->singleton('hash.driver', function ($app) {
            return $app['hash']->driver();
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
