<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class EventServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        $app = app();
        $app->alias(Dispatcher::class, 'events');
        $app->singleton(Dispatcher::class, Dispatcher::class);
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        $dispatch = app('events');
        $events = config('listeners.events', []);
        foreach ((array)$events as $event => $listen) {
            $dispatch->listen($event, $listen);
        }
    }
}
