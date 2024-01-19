<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Concerns;

use Closure;
use Mini\Contracts\View\View as ViewContract;
use Mini\Support\Str;

trait ManagesEvents
{
    /**
     * Register a view creator event.
     *
     * @param array|string $views
     * @param string|Closure $callback
     * @return array
     */
    public function creator(array|string $views, string|Closure $callback): array
    {
        $creators = [];

        foreach ((array)$views as $view) {
            $creators[] = $this->addViewEvent($view, $callback, 'creating: ');
        }

        return $creators;
    }

    /**
     * Register multiple view composers via an array.
     *
     * @param array $composers
     * @return array
     */
    public function composers(array $composers): array
    {
        $registered = [];

        foreach ($composers as $callback => $views) {
            $registered = array_merge($registered, $this->composer($views, $callback));
        }

        return $registered;
    }

    /**
     * Register a view composer event.
     *
     * @param array|string $views
     * @param string|Closure $callback
     * @return array
     */
    public function composer(array|string $views, string|Closure $callback): array
    {
        $composers = [];

        foreach ((array)$views as $view) {
            $composers[] = $this->addViewEvent($view, $callback, 'composing: ');
        }

        return $composers;
    }

    /**
     * Add an event for a given view.
     *
     * @param string $view
     * @param \Closure|string $callback
     * @param string $prefix
     * @return \Closure|null
     */
    protected function addViewEvent($view, $callback, $prefix = 'composing: '): ?Closure
    {
        $view = $this->normalizeName($view);

        if ($callback instanceof Closure) {
            $this->addEventListener($prefix . $view, $callback);

            return $callback;
        }

        if (is_string($callback)) {
            return $this->addClassEvent($view, $callback, $prefix);
        }
    }

    /**
     * Register a class based view composer.
     *
     * @param string $view
     * @param string $class
     * @param string $prefix
     * @return \Closure
     */
    protected function addClassEvent(string $view, string $class, string $prefix): Closure
    {
        $name = $prefix . $view;

        // When registering a class based view "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable view composers.
        $callback = $this->buildClassEventCallback(
            $class, $prefix
        );

        $this->addEventListener($name, $callback);

        return $callback;
    }

    /**
     * Build a class based container callback Closure.
     *
     * @param string $class
     * @param string $prefix
     * @return \Closure
     */
    protected function buildClassEventCallback(string $class, string $prefix): callable
    {
        [$class, $method] = $this->parseClassEvent($class, $prefix);

        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
        return function () use ($class, $method) {
            return call_user_func_array(
                [$this->container->make($class), $method], func_get_args()
            );
        };
    }

    /**
     * Parse a class based composer name.
     *
     * @param string $class
     * @param string $prefix
     * @return array
     */
    protected function parseClassEvent(string $class, string $prefix): array
    {
        return Str::parseCallback($class, $this->classEventMethodForPrefix($prefix));
    }

    /**
     * Determine the class event method based on the given prefix.
     *
     * @param string $prefix
     * @return string
     */
    protected function classEventMethodForPrefix(string $prefix): string
    {
        return Str::contains($prefix, 'composing') ? 'compose' : 'create';
    }

    /**
     * Add a listener to the event dispatcher.
     *
     * @param string $name
     * @param \Closure $callback
     * @return void
     */
    protected function addEventListener($name, $callback): void
    {
        if (Str::contains($name, '*')) {
            $callback = static function ($name, array $data) use ($callback) {
                return $callback($data[0]);
            };
        }

        $this->events->listen($name, $callback);
    }

    /**
     * Call the composer for a given view.
     *
     * @param \Mini\Contracts\View\View $view
     * @return void
     */
    public function callComposer(ViewContract $view): void
    {
        $this->events->dispatch('composing: ' . $view->name(), [$view]);
    }

    /**
     * Call the creator for a given view.
     *
     * @param \Mini\Contracts\View\View $view
     * @return void
     */
    public function callCreator(ViewContract $view): void
    {
        $this->events->dispatch('creating: ' . $view->name(), [$view]);
    }
}
