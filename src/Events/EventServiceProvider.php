<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Mini\Contracts\Queue\Factory as QueueFactoryContract;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class EventServiceProvider implements ServiceProviderInterface
{
    public function register(?Server $server, ?int $workerId): void
    {
        $app = app();
        $app->alias(Dispatcher::class, 'events');
        $app->bind(Dispatcher::class, Dispatcher::class);
    }

    public function boot(?Server $server, ?int $workerId): void
    {
        // TODO: Implement boot() method.
    }
}
