<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Closure;
use Mini\Container\Container;
use Mini\Contracts\Support\Htmlable;
use Mini\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Component
{
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
     * @var \Mini\View\ComponentAttributeBag
     */
    public ComponentAttributeBag $attributes;

    /**
     * Get the view / view contents that represent the component.
     *
     * @return \Mini\View\View|\Mini\Contracts\Support\Htmlable|\Closure|string
     */
    abstract public function render();

    /**
     * Resolve the Blade view or view file that should be used when rendering the component.
     *
     * @return \Mini\View\View|\Mini\Contracts\Support\Htmlable|\Closure|string
     */
    public function resolveView()
    {
        $view = $this->render();

        if ($view instanceof View) {
            return $view;
        }

        if ($view instanceof Htmlable) {
            return $view;
        }

        $resolver = function ($view) {
            $factory = Container::getInstance()->make('view');

            return $factory->exists($view)
                ? $view
                : $this->createBladeViewFromString($factory, $view);
        };

        return $view instanceof Closure ? static function (array $data = []) use ($view, $resolver) {
            return $resolver($view($data));
        }
            : $resolver($view);
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
            $directory = Container::getInstance()['config']->get('view.compiled')
        );

        if (!is_file($viewFile = $directory . '/' . sha1($contents) . '.blade.php')) {
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
     * @throws \ReflectionException
     */
    protected function extractPublicProperties(): array
    {
        $class = get_class($this);

        if (!isset(static::$propertyCache[$class])) {
            $reflection = new ReflectionClass($this);

            static::$propertyCache[$class] = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
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
     * @return mixed
     */
    protected function createVariableFromMethod(ReflectionMethod $method)
    {
        return $method->getNumberOfParameters() === 0
            ? $this->createInvokableVariable($method->getName())
            : Closure::fromCallable([$this, $method->getName()]);
    }

    /**
     * Create an invokable, toStringable variable for the given component method.
     *
     * @param string $method
     * @return \Mini\View\InvokableComponentVariable
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
    protected function shouldIgnore($name): bool
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
            'resolveView',
            'shouldRender',
            'view',
            'withName',
            'withAttributes',
        ], $this->except);
    }

    /**
     * Set the component alias name.
     *
     * @param string $name
     * @return $this
     */
    public function withName($name): self
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
        $this->attributes = $this->attributes ?: new ComponentAttributeBag;

        $this->attributes->setAttributes($attributes);

        return $this;
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
}
