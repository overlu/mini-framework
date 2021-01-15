<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

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
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $app = app();
        $app->singleton('hash', function ($app) {
            return new HashManager($app);
        });

        $app->singleton('hash.driver', function ($app) {
            return $app['hash']->driver();
        });
    }

    public function boot(?Server $server, ?int $workerId): void
    {
        // TODO: Implement boot() method.
    }
}
