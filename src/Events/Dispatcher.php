<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Events;

use Closure;
use Exception;
use Mini\Container\Container;
use Mini\Contracts\Broadcasting\Factory as BroadcastFactory;
use Mini\Contracts\Broadcasting\ShouldBroadcast;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Container\Container as ContainerContract;
use Mini\Contracts\Event as DispatcherContract;
use Mini\Contracts\Queue\Queue;
use Mini\Contracts\Queue\ShouldQueue;
use Mini\Support\Arr;
use Mini\Support\Str;
use Mini\Support\Traits\Macroable;
use ReflectionClass;
use ReflectionException;

class Dispatcher implements DispatcherContract
{
    use Macroable;

    /**
     * The IoC container instance.
     *
     * @var ContainerContract
     */
    protected ContainerContract $container;

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected array $listeners = [];

    /**
     * The wildcard listeners.
     *
     * @var array
     */
    protected array $wildcards = [];

    /**
     * The cached wildcard listeners.
     *
     * @var array
     */
    protected array $wildcardsCache = [];

    /**
     * The queue resolver instance.
     *
     * @var callable
     */
    protected $queueResolver;

    /**
     * Create a new event dispatcher instance.
     *
     * @param ContainerContract|null $container
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container ?: new Container;
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
        foreach ((array)$events as $event) {
            if (Str::contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                $this->listeners[$event][] = $this->makeListener($listener);
            }
        }
    }

    /**
     * Setup a wildcard listener callback.
     *
     * @param string $event
     * @param string|Closure $listener
     * @return void
     */
    protected function setupWildcardListen(string $event, string|Closure $listener): void
    {
        $this->wildcards[$event][] = $this->makeListener($listener, true);

        $this->wildcardsCache = [];
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) ||
            isset($this->wildcards[$eventName]) ||
            $this->hasWildcardListeners($eventName);
    }

    /**
     * Determine if the given event has any wildcard listeners.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasWildcardListeners(string $eventName): bool
    {
        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register an event and payload to be fired later.
     *
     * @param string $event
     * @param array $payload
     * @return void
     */
    public function push(string $event, array $payload = []): void
    {
        $this->listen($event . '_pushed', function () use ($event, $payload) {
            $this->dispatch($event, $payload);
        });
    }

    /**
     * Flush a set of pushed events.
     *
     * @param string $event
     * @return void
     */
    public function flush(string $event): void
    {
        $this->dispatch($event . '_pushed');
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param object|string $subscriber
     * @return void
     */
    public function subscribe(object|string $subscriber): void
    {
        $subscriber = $this->resolveSubscriber($subscriber);

        $events = $subscriber->subscribe($this);

        if (is_array($events)) {
            foreach ($events as $event => $listeners) {
                foreach ($listeners as $listener) {
                    $this->listen($event, $listener);
                }
            }
        }
    }

    /**
     * Resolve the subscriber instance.
     *
     * @param object|string $subscriber
     * @return mixed
     */
    protected function resolveSubscriber(object|string $subscriber): mixed
    {
        if (is_string($subscriber)) {
            return $this->container->make($subscriber);
        }

        return $subscriber;
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param object|string $event
     * @param mixed|array $payload
     * @return array|null
     */
    public function until(object|string $event, mixed $payload = []): ?array
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     *  Fire an event and call the listeners.
     *
     * @param $event
     * @param mixed $payload
     * @param bool $halt
     * @return int|bool
     */
    public function task($event, mixed $payload = [], bool $halt = false): int|bool
    {
        if (is_callable($event)) {
            $taskData = [
                'type' => 'callable',
                'callable' => \Opis\Closure\serialize($event),
                'params' => $payload
            ];
        } else {
            $taskData = [
                'type' => 'events',
                'params' => [$event, $payload, $halt]
            ];
        }
        return server()->task($taskData);
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param object|string $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function dispatch(object|string $event, mixed $payload = [], bool $halt = false): ?array
    {
        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        [$event, $payload] = $this->parseEventAndPayload(
            $event, $payload
        );

        /*if ($this->shouldBroadcast($payload)) {
            $this->broadcastEvent($payload[0]);
        }*/

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if ($halt && !is_null($response)) {
                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @param mixed $event
     * @param mixed $payload
     * @return array
     */
    protected function parseEventAndPayload(mixed $event, mixed $payload): array
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }

        return [$event, Arr::wrap($payload)];
    }

    /**
     * Determine if the payload has a broadcastable event.
     *
     * @param array $payload
     * @return bool
     */
    protected function shouldBroadcast(array $payload): bool
    {
        return isset($payload[0]) &&
            $payload[0] instanceof ShouldBroadcast &&
            $this->broadcastWhen($payload[0]);
    }

    /**
     * Check if event should be broadcasted by condition.
     *
     * @param mixed $event
     * @return bool
     */
    protected function broadcastWhen(mixed $event): bool
    {
        return method_exists($event, 'broadcastWhen')
            ? $event->broadcastWhen() : true;
    }

    /**
     * Broadcast the given event class.
     *
     * @param ShouldBroadcast $event
     * @return void
     */
    protected function broadcastEvent(ShouldBroadcast $event): void
    {
        $this->container->make(BroadcastFactory::class)->queue($event);
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param string $eventName
     * @return array
     */
    public function getListeners(string $eventName): array
    {
        $listeners = $this->listeners[$eventName] ?? [];

        $listeners = array_merge(
            $listeners,
            $this->wildcardsCache[$eventName] ?? $this->getWildcardListeners($eventName)
        );

        return class_exists($eventName, false)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : $listeners;
    }

    /**
     * Get the wildcard listeners for the event.
     *
     * @param string $eventName
     * @return array
     */
    protected function getWildcardListeners(string $eventName): array
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $this->wildcardsCache[$eventName] = $wildcards;
    }

    /**
     * Add the listeners for the event's interfaces to the given array.
     *
     * @param string $eventName
     * @param array $listeners
     * @return array
     */
    protected function addInterfaceListeners(string $eventName, array $listeners = []): array
    {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $names) {
                    $listeners = array_merge($listeners, (array)$names);
                }
            }
        }

        return $listeners;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param string|array|Closure $listener
     * @param bool $wildcard
     * @return Closure
     */
    public function makeListener(string|array|Closure $listener, bool $wildcard = false): callable
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener, $wildcard);
        }

        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return $this->createClassListener($listener, $wildcard);
        }

        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return $listener($event, $payload);
            }

            return $listener(...array_values($payload));
        };
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * @param string|array $listener
     * @param bool $wildcard
     * @return Closure
     */
    public function createClassListener(string|array $listener, bool $wildcard = false): callable
    {
        return function ($event, $payload) use ($listener, $wildcard) {
            if ($wildcard) {
                return call_user_func($this->createClassCallable($listener), $event, $payload);
            }

            return call_user_func_array(
                $this->createClassCallable($listener), $payload
            );
        };
    }

    /**
     * Create the class based event callable.
     *
     * @param array|string $listener
     * @return callable|array
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function createClassCallable(array|string $listener): callable|array
    {
        [$class, $method] = is_array($listener)
            ? $listener
            : $this->parseClassCallable($listener);

        // 屏蔽队列模式
        /*if ($this->handlerShouldBeQueued($class)) {
            return $this->createQueuedHandlerCallable($class, $method);
        }*/

        return [$this->container->make($class), $method];
    }

    /**
     * Parse the class listener into class and method.
     *
     * @param string $listener
     * @return array
     */
    protected function parseClassCallable(string $listener): array
    {
        return Str::parseCallback($listener, 'handle');
    }

    /**
     * Determine if the event handler class should be queued.
     *
     * @param string $class
     * @return bool|null
     */
    protected function handlerShouldBeQueued(string $class): ?bool
    {
        try {
            return (new ReflectionClass($class))->implementsInterface(
                ShouldQueue::class
            );
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a callable for putting an event handler on the queue.
     *
     * @param string $class
     * @param string $method
     * @return Closure
     */
    protected function createQueuedHandlerCallable(string $class, string $method): callable
    {
        return function () use ($class, $method) {
            $arguments = array_map(function ($a) {
                return is_object($a) ? clone $a : $a;
            }, func_get_args());

            if ($this->handlerWantsToBeQueued($class, $arguments)) {
                $this->queueHandler($class, $method, $arguments);
            }
        };
    }

    /**
     * Determine if the event handler wants to be queued.
     *
     * @param string $class
     * @param array $arguments
     * @return bool
     */
    protected function handlerWantsToBeQueued(string $class, array $arguments): bool
    {
        $instance = $this->container->make($class);

        if (method_exists($instance, 'shouldQueue')) {
            return $instance->shouldQueue($arguments[0]);
        }

        return true;
    }

    /**
     * Queue the handler class.
     *
     * @param string $class
     * @param string $method
     * @param array $arguments
     * @return void
     */
    protected function queueHandler(string $class, string $method, array $arguments): void
    {
        [$listener, $job] = $this->createListenerAndJob($class, $method, $arguments);

        $connection = $this->resolveQueue()->connection(
            $listener->connection ?? null
        );

        $queue = method_exists($listener, 'viaQueue')
            ? $listener->viaQueue()
            : $listener->queue ?? null;

        isset($listener->delay)
            ? $connection->laterOn($queue, $listener->delay, $job)
            : $connection->pushOn($queue, $job);
    }

    /**
     * Create the listener and job for a queued listener.
     *
     * @param string $class
     * @param string $method
     * @param array $arguments
     * @return array
     * @throws ReflectionException
     */
    protected function createListenerAndJob(string $class, string $method, array $arguments): array
    {
        $listener = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        return [$listener, $this->propagateListenerOptions(
            $listener, new CallQueuedListener($class, $method, $arguments)
        )];
    }

    /**
     * Propagate listener options to the job.
     *
     * @param mixed $listener
     * @param mixed $job
     * @return mixed
     */
    protected function propagateListenerOptions(mixed $listener, mixed $job): mixed
    {
        return tap($job, function ($job) use ($listener) {
            $job->tries = $listener->tries ?? null;
            $job->retryAfter = method_exists($listener, 'retryAfter')
                ? $listener->retryAfter() : ($listener->retryAfter ?? null);
            $job->timeout = $listener->timeout ?? null;
            $job->timeoutAt = method_exists($listener, 'retryUntil')
                ? $listener->retryUntil() : null;
        });
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param string $event
     * @return void
     */
    public function forget(string $event): void
    {
        if (Str::contains($event, '*')) {
            unset($this->wildcards[$event]);
        } else {
            unset($this->listeners[$event]);
        }

        foreach ($this->wildcardsCache as $key => $listeners) {
            if (Str::is($event, $key)) {
                unset($this->wildcardsCache[$key]);
            }
        }
    }

    /**
     * Forget all of the pushed listeners.
     *
     * @return void
     */
    public function forgetPushed(): void
    {
        foreach ($this->listeners as $key => $value) {
            if (Str::endsWith($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    /**
     * Get the queue implementation from the resolver.
     *
     * @return Queue
     */
    protected function resolveQueue(): Queue
    {
        return call_user_func($this->queueResolver);
    }

    /**
     * Set the queue resolver implementation.
     *
     * @param callable $resolver
     * @return $this
     */
    public function setQueueResolver(callable $resolver): self
    {
        $this->queueResolver = $resolver;

        return $this;
    }
}
