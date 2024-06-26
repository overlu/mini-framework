<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Mini\Contracts\Container\Container;
use Mini\Contracts\Events\Dispatcher;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\View as FactoryContract;
use Mini\Contracts\View\Engine;
use Mini\Support\Arr;
use Mini\Support\Traits\Macroable;
use Mini\View\Engines\EngineResolver;
use InvalidArgumentException;

class Factory implements FactoryContract
{
    use Macroable,
        Concerns\ManagesComponents,
        Concerns\ManagesEvents,
        Concerns\ManagesFragments,
        Concerns\ManagesLayouts,
        Concerns\ManagesLoops,
        Concerns\ManagesStacks,
        Concerns\ManagesTranslations;

    /**
     * The engine implementation.
     *
     * @var EngineResolver
     */
    protected EngineResolver $engines;

    /**
     * The view finder implementation.
     *
     * @var ViewFinderInterface
     */
    protected ViewFinderInterface $finder;

    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected Dispatcher $events;

    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Data that should be available to all templates.
     *
     * @var array
     */
    protected array $shared = [];

    /**
     * The extension to engine bindings.
     *
     * @var array
     */
    protected array $extensions = [
        'blade.php' => 'blade',
        'php' => 'php',
        'css' => 'file',
        'html' => 'file',
    ];

    /**
     * The view composer events.
     *
     * @var array
     */
    protected array $composers = [];

    /**
     * The number of active rendering operations.
     *
     * @var int
     */
    protected int $renderCount = 0;

    /**
     * The "once" block IDs that have been rendered.
     *
     * @var array
     */
    protected array $renderedOnce = [];

    /**
     * Create a new view factory instance.
     *
     * @param EngineResolver $engines
     * @param ViewFinderInterface $finder
     * @param Dispatcher|null $events
     */
    public function __construct(EngineResolver $engines, ViewFinderInterface $finder, ?Dispatcher $events)
    {
        $this->finder = $finder;
        $this->events = $events;
        $this->engines = $engines;

        $this->share('__env', $this);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $path
     * @param array $data
     * @param array $mergeData
     * @return \Mini\Contracts\View\View |mixed
     */
    public function file(string $path, array $data = [], array $mergeData = []): mixed
    {
        $data = array_merge($mergeData, $this->parseData($data));

        return tap($this->viewInstance($path, $path, $data), function ($view) {
            $this->callCreator($view);
        });
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return \Mini\Contracts\View\View|mixed
     */
    public function make(string $view, array $data = [], array $mergeData = []): mixed
    {
        $path = $this->finder->find(
            $view = $this->normalizeName($view)
        );

        // Next, we will create the view instance and call the view creator for the view
        // which can set any data, etc. Then we will return the view instance back to
        // the caller for rendering or performing other view manipulations on this.
        $data = array_merge($mergeData, $this->parseData($data));

        return tap($this->viewInstance($view, $path, $data), function ($view) {
            $this->callCreator($view);
        });
    }

    /**
     * Get the first view that actually exists from the given list.
     *
     * @param array $views
     * @param array $data
     * @param array $mergeData
     * @return \Mini\Contracts\View\View|mixed
     *
     */
    public function first(array $views, array $data = [], array $mergeData = []): mixed
    {
        $view = Arr::first($views, function ($view) {
            return $this->exists($view);
        });

        if (!$view) {
            throw new InvalidArgumentException('None of the views in the given array exist.');
        }

        return $this->make($view, $data, $mergeData);
    }

    /**
     * Get the rendered content of the view based on a given condition.
     *
     * @param bool $condition
     * @param string $view
     * @param array|Arrayable $data
     * @param array $mergeData
     * @return string|mixed
     */
    public function renderWhen(bool $condition, string $view, Arrayable|array $data = [], array $mergeData = []): mixed
    {
        if (!$condition) {
            return '';
        }

        return $this->make($view, $this->parseData($data), $mergeData)->render();
    }

    /**
     * Get the rendered content of the view based on the negation of a given condition.
     *
     * @param bool $condition
     * @param string $view
     * @param array|Arrayable $data
     * @param array $mergeData
     * @return string
     */
    public function renderUnless(bool $condition, string $view, Arrayable|array $data = [], array $mergeData = []): string
    {
        return $this->renderWhen(!$condition, $view, $data, $mergeData);
    }

    /**
     * Get the rendered contents of a partial from a loop.
     *
     * @param string $view
     * @param array $data
     * @param string $iterator
     * @param string $empty
     * @return string
     */
    public function renderEach(string $view, array $data, string $iterator, string $empty = 'raw|'): string
    {
        $result = '';

        // If is actually data in the array, we will loop through the data and append
        // an instance of the partial view to the final result HTML passing in the
        // iterated value of this data array, allowing the views to access them.
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= $this->make(
                    $view, ['key' => $key, $iterator => $value]
                )->render();
            }
        }

        // If there is no data in the array, we will render the contents of the empty
        // view. Alternatively, the "empty view" could be a raw string that begins
        // with "raw|" for convenience and to let this know that it is a string.
        else {
            $result = str_starts_with($empty, 'raw|')
                ? substr($empty, 4)
                : $this->make($empty)->render();
        }

        return $result;
    }

    /**
     * Normalize a view name.
     *
     * @param string $name
     * @return string
     */
    protected function normalizeName(string $name): string
    {
        return ViewName::normalize($name);
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param mixed $data
     * @return array
     */
    protected function parseData(Arrayable|array $data): array
    {
        return $data instanceof Arrayable ? $data->toArray() : $data;
    }

    /**
     * Create a new view instance from the given arguments.
     *
     * @param string $view
     * @param string $path
     * @param array|Arrayable $data
     * @return \Mini\Contracts\View\View
     */
    protected function viewInstance(string $view, string $path, Arrayable|array $data): View
    {
        return new View($this, $this->getEngineFromPath($path), $view, $path, $data);
    }

    /**
     * Determine if a given view exists.
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool
    {
        try {
            $this->finder->find($view);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate view engine for the given path.
     *
     * @param string $path
     * @return Engine
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath(string $path): Engine
    {
        if (!$extension = $this->getExtension($path)) {
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}.");
        }

        $engine = $this->extensions[$extension];

        return $this->engines->resolve($engine);
    }

    /**
     * Get the extension used by the view file.
     *
     * @param string $path
     * @return string|null
     */
    protected function getExtension(string $path): ?string
    {
        $extensions = array_keys($this->extensions);

        return Arr::first($extensions, function ($value) use ($path) {
            return str_ends_with($path, '.' . $value);
        });
    }

    /**
     * Add a piece of shared data to the environment.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return mixed
     */
    public function share(array|string $key, mixed $value = null): mixed
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }

    /**
     * Increment the rendering counter.
     *
     * @return void
     */
    public function incrementRender(): void
    {
        $this->renderCount++;
    }

    /**
     * Decrement the rendering counter.
     *
     * @return void
     */
    public function decrementRender(): void
    {
        $this->renderCount--;
    }

    /**
     * Check if there are no active render operations.
     *
     * @return bool
     */
    public function doneRendering(): bool
    {
        return $this->renderCount === 0;
    }

    /**
     * Determine if the given once token has been rendered.
     *
     * @param string $id
     * @return bool
     */
    public function hasRenderedOnce(string $id): bool
    {
        return isset($this->renderedOnce[$id]);
    }

    /**
     * Mark the given once token as having been rendered.
     *
     * @param string $id
     * @return void
     */
    public function markAsRenderedOnce(string $id): void
    {
        $this->renderedOnce[$id] = true;
    }

    /**
     * Add a location to the array of view locations.
     *
     * @param string $location
     * @return void
     */
    public function addLocation(string $location): void
    {
        $this->finder->addLocation($location);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param string $namespace
     * @param array|string $hints
     * @return $this
     */
    public function addNamespace(string $namespace, array|string $hints): self
    {
        $this->finder->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Prepend a new namespace to the loader.
     *
     * @param string $namespace
     * @param array|string $hints
     * @return $this
     */
    public function prependNamespace(string $namespace, array|string $hints): self
    {
        $this->finder->prependNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param string $namespace
     * @param array|string $hints
     * @return $this
     */
    public function replaceNamespace(string $namespace, array|string $hints): self
    {
        $this->finder->replaceNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Register a valid view extension and its engine.
     *
     * @param string $extension
     * @param string $engine
     * @param \Closure|null $resolver
     * @return void
     */
    public function addExtension(string $extension, string $engine, ?\Closure $resolver = null): void
    {
        $this->finder->addExtension($extension);

        if (isset($resolver)) {
            $this->engines->register($engine, $resolver);
        }

        unset($this->extensions[$extension]);

        $this->extensions = array_merge([$extension => $engine], $this->extensions);
    }

    /**
     * Flush all of the factory state like sections and stacks.
     *
     * @return void
     */
    public function flushState(): void
    {
        $this->renderCount = 0;
        $this->renderedOnce = [];

        $this->flushSections();
        $this->flushStacks();
        $this->flushComponents();
        $this->flushFragments();
    }

    /**
     * Flush all of the section contents if done rendering.
     *
     * @return void
     */
    public function flushStateIfDoneRendering(): void
    {
        if ($this->doneRendering()) {
            $this->flushState();
        }
    }

    /**
     * Get the extension to engine bindings.
     *
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Get the engine resolver instance.
     *
     * @return EngineResolver
     */
    public function getEngineResolver(): EngineResolver
    {
        return $this->engines;
    }

    /**
     * Get the view finder instance.
     *
     * @return ViewFinderInterface
     */
    public function getFinder(): ViewFinderInterface
    {
        return $this->finder;
    }

    /**
     * Set the view finder instance.
     *
     * @param ViewFinderInterface $finder
     * @return void
     */
    public function setFinder(ViewFinderInterface $finder): void
    {
        $this->finder = $finder;
    }

    /**
     * Flush the cache of views located by the finder.
     *
     * @return void
     */
    public function flushFinderCache(): void
    {
        $this->getFinder()->flush();
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events): void
    {
        $this->events = $events;
    }

    /**
     * Get the IoC container instance.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param Container $container
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Get an item from the shared data.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function shared(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->shared, $key, $default);
    }

    /**
     * Get all of the shared data for the environment.
     *
     * @return array
     */
    public function getShared(): array
    {
        return $this->shared;
    }
}
