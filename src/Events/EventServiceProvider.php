<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Mini\Service\AbstractServiceProvider;

class EventServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Mini\Contracts\Event::class, Dispatcher::class);
        $this->app->alias(\Mini\Contracts\Event::class, 'events');
    }

    public function boot(): void
    {
        $dispatch = $this->app['events'];
        $events = config('listeners.events', []);
        foreach ((array)$events as $event => $listen) {
            $dispatch->listen($event, $listen);
        }
    }
}
