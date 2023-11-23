<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;
use ReflectionException;

class EventServiceProvider extends AbstractServiceProvider
{
    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function register(): void
    {
        $this->app->alias(Dispatcher::class, 'events');
        $this->app->singleton(Dispatcher::class, Dispatcher::class);
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
