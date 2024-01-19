<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Closure;
use Mini\Contracts\Events\Dispatcher as DispatcherContract;
use Mini\Support\Traits\ForwardsCalls;

class NullDispatcher implements DispatcherContract
{
    use ForwardsCalls;

    /**
     * The underlying event dispatcher instance.
     */
    protected DispatcherContract $dispatcher;

    /**
     * Create a new event dispatcher instance that does not fire.
     *
     * @param DispatcherContract $dispatcher
     * @return void
     */
    public function __construct(DispatcherContract $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Don't fire an event.
     *
     * @param object|string $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array
    {
        return null;
    }

    public function task($event, mixed $payload = [], bool $halt = false): int|bool
    {
        return false;
    }

    /**
     * Don't register an event and payload to be fired later.
     *
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function push(string $event, array $payload = []): void
    {
    }

    /**
     * Don't dispatch an event.
     *
     * @param object|string $event
     * @param mixed|array $payload
     * @return array|null
     */
    public function until(object|string $event, mixed $payload = []): ?array
    {
        return null;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param array|string $events
     * @param string|Closure $listener
     * @return void
     */
    public function listen(array|string $events, string|Closure $listener): void
    {
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param object|string $subscriber
     * @return void
     */
    public function subscribe(object|string $subscriber): void
    {
        $this->dispatcher->subscribe($subscriber);
    }

    /**
     * Flush a set of pushed events.
     *
     * @param string $event
     * @return void
     */
    public function flush(string $event): void
    {
        $this->dispatcher->flush($event);
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param string $event
     * @return void
     */
    public function forget(string $event): void
    {
        $this->dispatcher->forget($event);
    }

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed(): void
    {
        $this->dispatcher->forgetPushed();
    }

    /**
     * Dynamically pass method calls to the underlying dispatcher.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->dispatcher, $method, $parameters);
    }
}
