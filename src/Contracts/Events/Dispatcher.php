<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Events;

use Psr\EventDispatcher\EventDispatcherInterface;

interface Dispatcher extends EventDispatcherInterface
{
    /**
     * Register an event listener with the dispatcher.
     *
     * @param array|string $events
     * @param string|\Closure $listener
     * @return void
     */
    public function listen(array|string $events, string|\Closure $listener): void;

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param object|string $subscriber
     * @return void
     */
    public function subscribe(object|string $subscriber): void;

    /**
     * Dispatch an event until the first non-null response is returned.
     *
     * @param object|string $event
     * @param mixed $payload
     * @return array|null
     */
    public function until(object|string $event, mixed $payload = []): ?array;

    /**
     * Dispatch an event and call the listeners.
     *
     * @param object|string $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array;

    public function task($event, mixed $payload = [], bool $halt = false): int|bool;

    /**
     * Register an event and payload to be fired later.
     *
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function push(string $event, array $payload = []): void;

    /**
     * Flush a set of pushed events.
     *
     * @param string $event
     * @return void
     */
    public function flush(string $event): void;

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param string $event
     * @return void
     */
    public function forget(string $event): void;

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed(): void;
}
