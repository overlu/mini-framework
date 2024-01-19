<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Concerns;

use Closure;
use Mini\Contracts\Events\Dispatcher;
use Mini\Events\NullDispatcher;
use Mini\Support\Arr;
use InvalidArgumentException;
use RuntimeException;

trait HasEvents
{
    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array
     */
    protected array $dispatchesEvents = [];

    /**
     * User exposed observable events.
     *
     * These are extra user-defined events observers may subscribe to.
     *
     * @var array
     */
    protected array $observables = [];

    /**
     * Register observers with the model.
     *
     * @param object|array|string $classes
     * @return void
     *
     * @throws RuntimeException
     */
    public static function observe(object|array|string $classes): void
    {
        $instance = new static;

        foreach (Arr::wrap($classes) as $class) {
            $instance->registerObserver($class);
        }
    }

    /**
     * Register a single observer with the model.
     *
     * @param object|string $class
     * @return void
     *
     * @throws RuntimeException
     */
    protected function registerObserver(object|string $class): void
    {
        $className = $this->resolveObserverClassName($class);

        // When registering a model observer, we will spin through the possible events
        // and determine if this observer has that method. If it does, we will hook
        // it into the model's event system, making it convenient to watch these.
        foreach ($this->getObservableEvents() as $event) {
            if (method_exists($class, $event)) {
                static::registerModelEvent($event, $className . '@' . $event);
            }
        }
    }

    /**
     * Resolve the observer's class name from an object or string.
     *
     * @param object|string $class
     * @return object|string
     *
     */
    private function resolveObserverClassName(object|string $class): object|string
    {
        if (is_object($class)) {
            return get_class($class);
        }

        if (class_exists($class)) {
            return $class;
        }

        throw new InvalidArgumentException('Unable to find observer: ' . $class);
    }

    /**
     * Get the observable event names.
     *
     * @return array
     */
    public function getObservableEvents(): array
    {
        return array_merge(
            [
                'retrieved', 'creating', 'created', 'updating', 'updated',
                'saving', 'saved', 'restoring', 'restored', 'replicating',
                'deleting', 'deleted', 'forceDeleted',
            ],
            $this->observables
        );
    }

    /**
     * Set the observable event names.
     *
     * @param array $observables
     * @return $this
     */
    public function setObservableEvents(array $observables): self
    {
        $this->observables = $observables;

        return $this;
    }

    /**
     * Add an observable event name.
     *
     * @param array|mixed $observables
     * @return void
     */
    public function addObservableEvents(mixed $observables): void
    {
        $this->observables = array_unique(array_merge(
            $this->observables, is_array($observables) ? $observables : func_get_args()
        ));
    }

    /**
     * Remove an observable event name.
     *
     * @param array|mixed $observables
     * @return void
     */
    public function removeObservableEvents(mixed $observables): void
    {
        $this->observables = array_diff(
            $this->observables, is_array($observables) ? $observables : func_get_args()
        );
    }

    /**
     * Register a model event with the dispatcher.
     *
     * @param string $event
     * @param string|Closure $callback
     * @return void
     */
    protected static function registerModelEvent(string $event, string|Closure $callback): void
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->listen("eloquent.{$event}: {$name}", $callback);
        }
    }

    /**
     * Fire the given event for the model.
     *
     * @param string $event
     * @param bool $halt
     * @return mixed
     */
    protected function fireModelEvent(string $event, bool $halt = true): mixed
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }

        // First, we will get the proper method to call on the event dispatcher, and then we
        // will attempt to fire a custom, object based event for the given event. If that
        // returns a result we can return that result, or we'll call the string events.
        $method = $halt ? 'until' : 'dispatch';

        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event, $method)
        );

        if ($result === false) {
            return false;
        }

        return !empty($result) ? $result : static::$dispatcher->{$method}(
            "eloquent.{$event}: " . static::class, $this
        );
    }

    /**
     * Fire a custom model event for the given event.
     *
     * @param string $event
     * @param string $method
     * @return mixed|null
     */
    protected function fireCustomModelEvent(string $event, string $method): mixed
    {
        if (!isset($this->dispatchesEvents[$event])) {
            return null;
        }

        $result = static::$dispatcher->$method(new $this->dispatchesEvents[$event]($this));

        if (!is_null($result)) {
            return $result;
        }
        return null;
    }

    /**
     * Filter the model event results.
     *
     * @param mixed $result
     * @return mixed
     */
    protected function filterModelEventResults(mixed $result): mixed
    {
        if (is_array($result)) {
            $result = array_filter($result, function ($response) {
                return !is_null($response);
            });
        }

        return $result;
    }

    /**
     * Register a retrieved model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function retrieved(string|Closure $callback): void
    {
        static::registerModelEvent('retrieved', $callback);
    }

    /**
     * Register a saving model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function saving(string|Closure $callback): void
    {
        static::registerModelEvent('saving', $callback);
    }

    /**
     * Register a saved model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function saved(string|Closure $callback): void
    {
        static::registerModelEvent('saved', $callback);
    }

    /**
     * Register an updating model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function updating(string|Closure $callback): void
    {
        static::registerModelEvent('updating', $callback);
    }

    /**
     * Register an updated model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function updated(string|Closure $callback): void
    {
        static::registerModelEvent('updated', $callback);
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function creating(string|Closure $callback): void
    {
        static::registerModelEvent('creating', $callback);
    }

    /**
     * Register a created model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function created(string|Closure $callback): void
    {
        static::registerModelEvent('created', $callback);
    }

    /**
     * Register a replicating model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function replicating(string|Closure $callback): void
    {
        static::registerModelEvent('replicating', $callback);
    }

    /**
     * Register a deleting model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function deleting(string|Closure $callback): void
    {
        static::registerModelEvent('deleting', $callback);
    }

    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param string|Closure $callback
     * @return void
     */
    public static function deleted(string|Closure $callback): void
    {
        static::registerModelEvent('deleted', $callback);
    }

    /**
     * Remove all of the event listeners for the model.
     *
     * @return void
     */
    public static function flushEventListeners(): void
    {
        if (!isset(static::$dispatcher)) {
            return;
        }

        $instance = new static;

        foreach ($instance->getObservableEvents() as $event) {
            static::$dispatcher->forget("eloquent.{$event}: " . static::class);
        }

        foreach ($instance->dispatchesEvents as $event) {
            static::$dispatcher->forget($event);
        }
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return Dispatcher
     */
    public static function getEventDispatcher(): Dispatcher
    {
        return static::$dispatcher;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param Dispatcher $dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher): void
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Unset the event dispatcher for models.
     *
     * @return void
     */
    public static function unsetEventDispatcher(): void
    {
        static::$dispatcher = null;
    }

    /**
     * Execute a callback without firing any model events for any model type.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function withoutEvents(callable $callback): mixed
    {
        $dispatcher = static::getEventDispatcher();

        static::setEventDispatcher(new NullDispatcher($dispatcher));

        try {
            return $callback();
        } finally {
            if ($dispatcher) {
                static::setEventDispatcher($dispatcher);
            }
        }
    }
}
