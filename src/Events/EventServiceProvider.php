<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $app = app();
        $app->alias(Dispatcher::class, 'events');
        $app->singleton(Dispatcher::class, Dispatcher::class);
    }

    public function boot(?Server $server, ?int $workerId): void
    {
        $dispatch = app('events');
        $events = config('listeners.events', []);
        foreach ((array)$events as $event => $listen) {
            $dispatch->listen($event, $listen);
        }
    }
}
