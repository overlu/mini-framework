<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use ArrayAccess;
use BadMethodCallException;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\Support\MessageProvider;
use Mini\Contracts\Support\Renderable;
use Mini\Contracts\View\Engine;
use Mini\Contracts\View\View as ViewContract;
use Mini\Support\MessageBag;
use Mini\Support\Str;
use Mini\Support\Traits\Macroable;
use Mini\Support\ViewErrorBag;
use Throwable;

class View implements ArrayAccess, Htmlable, ViewContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The view factory instance.
     *
     * @var Factory
     */
    protected Factory $factory;

    /**
     * The engine implementation.
     *
     * @var Engine
     */
    protected Engine $engine;

    /**
     * The name of the view.
     *
     * @var string
     */
    protected string $view;

    /**
     * The array of view data.
     *
     * @var array
     */
    protected ?array $data;

    /**
     * The path to the view file.
     *
     * @var string
     */
    protected string $path;

    /**
     * Create a new view instance.
     *
     * @param Factory $factory
     * @param Engine $engine
     * @param string $view
     * @param string $path
     * @param mixed $data
     * @return void
     */
    public function __construct(Factory $factory, Engine $engine, string $view, string $path, $data = [])
    {
        $this->view = $view;
        $this->path = $path;
        $this->engine = $engine;
        $this->factory = $factory;

        $this->data = $data instanceof Arrayable ? $data->toArray() : (array)$data;
    }

    /**
     * Get the string contents of the view.
     *
     * @param callable|null $callback
     * @return array|string
     *
     * @throws \Throwable
     */
    public function render(callable $callback = null): string
    {
        try {
            $contents = $this->renderContents();

            $response = isset($callback) ? $callback($this, $contents) : null;

            // Once we have the contents of the view, we will flush the sections if we are
            // done rendering all views so that there is nothing left hanging over when
            // another view gets rendered in the future by the application developer.
            $this->factory->flushStateIfDoneRendering();

            return !is_null($response) ? $response : $contents;
        } catch (Throwable $e) {
            $this->factory->flushState();

            throw $e;
        }
    }

    /**
     * Get the contents of the view instance.
     *
     * @return string
     */
    protected function renderContents(): string
    {
        // We will keep track of the amount of views being rendered so we can flush
        // the section after the complete rendering operation is done. This will
        // clear out the sections for any separate views that may be rendered.
        $this->factory->incrementRender();

        $this->factory->callComposer($this);

        $contents = $this->getContents();

        // Once we've finished rendering the view, we'll decrement the render count
        // so that each sections get flushed out next time a view is created and
        // no old sections are staying around in the memory of an environment.
        $this->factory->decrementRender();

        return $contents;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @return string
     */
    protected function getContents(): string
    {
        return $this->engine->get($this->path, $this->gatherData());
    }

    /**
     * Get the data bound to the view instance.
     *
     * @return array
     */
    public function gatherData(): array
    {
        $data = array_merge($this->factory->getShared(), $this->data);

        foreach ($data as $key => $value) {
            if ($value instanceof Renderable) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Get the sections of the rendered view.
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function renderSections(): array
    {
        return $this->render(function () {
            return $this->factory->getSections();
        });
    }

    /**
     * Add a piece of data to the view.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function with(array|string $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Add a view instance to the view data.
     *
     * @param string $key
     * @param string $view
     * @param array $data
     * @return $this
     */
    public function nest(string $key, string $view, array $data = []): self
    {
        return $this->with($key, $this->factory->make($view, $data));
    }

    /**
     * Add validation errors to the view.
     *
     * @param MessageProvider|array $provider
     * @param string $bag
     * @return $this
     */
    public function withErrors($provider, string $bag = 'default'): self
    {
        return $this->with('errors', (new ViewErrorBag)->put(
            $bag, $this->formatErrors($provider)
        ));
    }

    /**
     * Parse the given errors into an appropriate value.
     *
     * @param MessageProvider|array|string $provider
     * @return MessageBag
     */
    protected function formatErrors($provider): MessageBag
    {
        return $provider instanceof MessageProvider
            ? $provider->getMessageBag()
            : new MessageBag((array)$provider);
    }

    /**
     * Get the name of the view.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->getName();
    }

    /**
     * Get the name of the view.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->view;
    }

    /**
     * Get the array of view data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the path to the view file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the path to the view.
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get the view factory instance.
     *
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * Get the view's rendering engine.
     *
     * @return Engine
     */
    public function getEngine(): Engine
    {
        return $this->engine;
    }

    /**
     * Determine if a piece of data is bound.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get a piece of bound data to the view.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, mixed $value): void
    {
        $this->with($key, $value);
    }

    /**
     * Unset a piece of data from the view.
     *
     * @param string $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Get a piece of data from the view.
     *
     * @param string $key
     * @return mixed
     */
    public function &__get(string $key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->with($key, $value);
    }

    /**
     * Check if a piece of data is bound to the view.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a piece of bound data from the view.
     *
     * @param string $key
     * @return void
     */
    public function __unset($key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Dynamically bind parameters to the view.
     *
     * @param string $method
     * @param array $parameters
     * @return \Mini\View\View
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (!Str::startsWith($method, 'with')) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     * @throws Throwable
     */
    public function toHtml(): string
    {
        return $this->render();
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function __toString()
    {
        return $this->render();
    }
}
