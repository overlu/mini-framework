<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class Event
 * @method static \Closure createClassListener(string $listener, bool $wildcard = false)
 * @method static \Closure makeListener(\Closure|string $listener, bool $wildcard = false)
 * @method static \Mini\Events\Dispatcher setQueueResolver(callable $resolver)
 * @method static array getListeners(string $eventName)
 * @method static array|null dispatch(string|object $event, mixed $payload = [], bool $halt = false)
 * @method static array|null task(string|object|callable $event, mixed $payload = [], bool $halt = false)
 * @method static array|null until(string|object $event, mixed $payload = [])
 * @method static bool hasListeners(string $eventName)
 * @method static void assertDispatched(string $event, callable|int $callback = null)
 * @method static void assertDispatchedTimes(string $event, int $times = 1)
 * @method static void assertNotDispatched(string $event, callable|int $callback = null)
 * @method static void flush(string $event)
 * @method static void forget(string $event)
 * @method static void forgetPushed()
 * @method static void listen(string|array $events, \Closure|string $listener)
 * @method static void push(string $event, array $payload = [])
 * @method static void subscribe(object|string $subscriber)
 * @package Mini\Facades
 * @see \Mini\Events\Dispatcher
 */
class Event extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'events';
    }
}