<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Closure;
use Mini\Container\Container;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\View\View as ViewContract;
use Mini\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Component
{
    /**
     * The properties / methods that should not be exposed to the component.
     *
     * @var array
     */
    protected array $except = [];

    /**
     * The component alias name.
     *
     * @var string
     */
    public string $componentName;

    /**
     * The component attributes.
     *
     * @var ComponentAttributeBag|null
     */
    public ?ComponentAttributeBag $attributes = null;

    /**
     * The view factory instance, if any.
     *
     * @var \Mini\Contracts\View\Factory|null
     */
    protected static ?\Mini\Contracts\View\Factory $factory = null;

    /**
     * The component resolver callback.
     *
     * @var (\Closure(string, array): Component)|null
     */
    protected static $componentsResolver;

    /**
     * The cache of blade view names, keyed by contents.
     *
     * @var array<string, string>
     */
    protected static array $bladeViewCache = [];

    /**
     * The cache of public property names, keyed by class.
     *
     * @var array
     */
    protected static array $propertyCache = [];

    /**
     * The cache of public method names, keyed by class.
     *
     * @var array
     */
    protected static array $methodCache = [];

    /**
     * The cache of constructor parameters, keyed by class.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static array $constructorParametersCache = [];

    /**
     * Get the view / view contents that represent the component.
     *
     * @return View|Htmlable|Closure|string
     */
    abstract public function render();

    /**
     * Resolve the component instance with the given data.
     *
     * @param array $data
     * @return static
     */
    public static function resolve(array $data): static
    {
        if (static::$componentsResolver) {
            return call_user_func(static::$componentsResolver, static::class, $data);
        }

        $parameters = static::extractConstructorParameters();

        $dataKeys = array_keys($data);

        if (empty(array_diff($parameters, $dataKeys))) {
            return new static(...array_intersect_key($data, array_flip($parameters)));
        }

        return Container::getInstance()->make(static::class, $data);
    }

    /**
     * Extract the constructor parameters for the component.
     *
     * @return array
     */
    protected static function extractConstructorParameters(): array
    {
        if (!isset(static::$constructorParametersCache[static::class])) {
            $class = new ReflectionClass(static::class);

            $constructor = $class->getConstructor();

            static::$constructorParametersCache[static::class] = $constructor
                ? collect($constructor->getParameters())->map->getName()->all()
                : [];
        }

        return static::$constructorParametersCache[static::class];
    }

    /**
     * Resolve the Blade view or view file that should be used when rendering the component.
     *
     * @return View|Htmlable|Closure|string
     */
    public function resolveView(): View|string|Closure|Htmlable
    {
        $view = $this->render();

        if ($view instanceof View) {
            return $view;
        }

        if ($view instanceof Htmlable) {
            return $view;
        }

        $resolver = function ($view) {
            if ($view instanceof ViewContract) {
                return $view;
            }

            return $this->extractBladeViewFromString($view);
        };

        return $view instanceof Closure ? static function (array $data = []) use ($view, $resolver) {
            return $resolver($view($data));
        }
            : $resolver($view);
    }

    /**
     * Create a Blade view with the raw component string content.
     *
     * @param string $contents
     * @return string
     */
    protected function extractBladeViewFromString(string $contents): string
    {
        $key = sprintf('%s::%s', static::class, $contents);

        if (isset(static::$bladeViewCache[$key])) {
            return static::$bladeViewCache[$key];
        }

        if (strlen($contents) <= PHP_MAXPATHLEN && $this->factory()->exists($contents)) {
            return static::$bladeViewCache[$key] = $contents;
        }

        return static::$bladeViewCache[$key] = $this->createBladeViewFromString($this->factory(), $contents);
    }

    /**
     * Create a Blade view with the raw component string content.
     *
     * @param Factory $factory
     * @param string $contents
     * @return string
     */
    protected function createBladeViewFromString(Factory $factory, string $contents): string
    {
        $factory->addNamespace(
            '__components',
            $directory = config('view.compiled')
        );

        if (!is_file($viewFile = $directory . '/' . hash('xxh128', $contents) . '.blade.php')) {
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }

            file_put_contents($viewFile, $contents);
        }

        return '__components::' . basename($viewFile, '.blade.php');
    }

    /**
     * Get the data that should be supplied to the view.
     *
     * @return array
     * @author Brent Roose
     *
     * @author Freek Van der Herten
     */
    public function data(): array
    {
        $this->attributes = $this->attributes ?: new ComponentAttributeBag;

        return array_merge($this->extractPublicProperties(), $this->extractPublicMethods());
    }

    /**
     * Extract the public properties for the component.
     *
     * @return array
     */
    protected function extractPublicProperties(): array
    {
        $class = get_class($this);

        if (!isset(static::$propertyCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$propertyCache[$class] = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
                ->reject(function (ReflectionProperty $property) {
                    return $property->isStatic();
                })
                ->reject(function (ReflectionProperty $property) {
                    return $this->shouldIgnore($property->getName());
                })
                ->map(static function (ReflectionProperty $property) {
                    return $property->getName();
                })->all();
        }

        $values = [];

        foreach (static::$propertyCache[$class] as $property) {
            $values[$property] = $this->{$property};
        }

        return $values;
    }

    /**
     * Extract the public methods for the component.
     *
     * @return array
     * @throws \ReflectionException
     */
    protected function extractPublicMethods(): array
    {
        $class = get_class($this);

        if (!isset(static::$methodCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$methodCache[$class] = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
                ->reject(function (ReflectionMethod $method) {
                    return $this->shouldIgnore($method->getName());
                })
                ->map(static function (ReflectionMethod $method) {
                    return $method->getName();
                });
        }

        $values = [];

        foreach (static::$methodCache[$class] as $method) {
            $values[$method] = $this->createVariableFromMethod(new ReflectionMethod($this, $method));
        }

        return $values;
    }

    /**
     * Create a callable variable from the given method.
     *
     * @param \ReflectionMethod $method
     * @return Closure|InvokableComponentVariable
     */
    protected function createVariableFromMethod(ReflectionMethod $method): Closure|InvokableComponentVariable
    {
        return $method->getNumberOfParameters() === 0
            ? $this->createInvokableVariable($method->getName())
            : Closure::fromCallable([$this, $method->getName()]);
    }

    /**
     * Create an invokable, toStringable variable for the given component method.
     *
     * @param string $method
     * @return InvokableComponentVariable
     */
    protected function createInvokableVariable(string $method): InvokableComponentVariable
    {
        return new InvokableComponentVariable(function () use ($method) {
            return $this->{$method}();
        });
    }

    /**
     * Determine if the given property / method should be ignored.
     *
     * @param string $name
     * @return bool
     */
    protected function shouldIgnore(string $name): bool
    {
        return Str::startsWith($name, '__') ||
            in_array($name, $this->ignoredMethods(), true);
    }

    /**
     * Get the methods that should be ignored.
     *
     * @return array
     */
    protected function ignoredMethods(): array
    {
        return array_merge([
            'data',
            'render',
            'resolve',
            'resolveView',
            'shouldRender',
            'view',
            'withName',
            'withAttributes',
            'flushCache',
            'forgetFactory',
            'forgetComponentsResolver',
            'resolveComponentsUsing',
        ], $this->except);
    }

    /**
     * Set the component alias name.
     *
     * @param string $name
     * @return $this
     */
    public function withName(string $name): self
    {
        $this->componentName = $name;

        return $this;
    }

    /**
     * Set the extra attributes that the component should make available.
     *
     * @param array $attributes
     * @return $this
     */
    public function withAttributes(array $attributes): self
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        $this->attributes->setAttributes($attributes);

        return $this;
    }

    /**
     * Get a new attribute bag instance.
     *
     * @param array $attributes
     * @return ComponentAttributeBag
     */
    protected function newAttributeBag(array $attributes = []): ComponentAttributeBag
    {
        return new ComponentAttributeBag($attributes);
    }

    /**
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender(): bool
    {
        return true;
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return ViewContract
     */
    public function view(string $view, array $data = [], array $mergeData = []): ViewContract
    {
        return $this->factory()->make($view, $data, $mergeData);
    }

    /**
     * Get the view factory instance.
     *
     * @return \Mini\Contracts\View\Factory
     */
    protected function factory(): \Mini\Contracts\View\Factory
    {
        if (is_null(static::$factory)) {
            static::$factory = app('view');
        }

        return static::$factory;
    }

    /**
     * Flush the component's cached state.
     *
     * @return void
     */
    public static function flushCache(): void
    {
        static::$bladeViewCache = [];
        static::$constructorParametersCache = [];
        static::$methodCache = [];
        static::$propertyCache = [];
    }

    /**
     * Forget the component's factory instance.
     *
     * @return void
     */
    public static function forgetFactory(): void
    {
        static::$factory = null;
    }

    /**
     * Forget the component's resolver callback.
     *
     * @return void
     *
     * @internal
     */
    public static function forgetComponentsResolver(): void
    {
        static::$componentsResolver = null;
    }

    /**
     * Set the callback that should be used to resolve components within views.
     *
     * @param Closure(string $component, array $data): Component  $resolver
     * @return void
     *
     * @internal
     */
    public static function resolveComponentsUsing($resolver): void
    {
        static::$componentsResolver = $resolver;
    }
}
