<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use ArrayIterator;
use Closure;
use Mini\Contracts\Support\DeferringDisplayableValue;
use Mini\Support\Enumerable;
use IteratorAggregate;

class InvokableComponentVariable implements DeferringDisplayableValue, IteratorAggregate
{
    /**
     * The callable instance to resolve the variable value.
     *
     * @var \Closure
     */
    protected Closure $callable;

    /**
     * Create a new variable instance.
     *
     * @param \Closure $callable
     * @return void
     */
    public function __construct(Closure $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Mini\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue()
    {
        return $this->__invoke();
    }

    /**
     * Get an interator instance for the variable.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        $result = $this->__invoke();

        return new ArrayIterator($result instanceof Enumerable ? $result->all() : $result);
    }

    /**
     * Dynamically proxy attribute access to the variable.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->__invoke()->{$key};
    }

    /**
     * Dynamically proxy method access to the variable.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, $parameters)
    {
        return $this->__invoke()->{$method}(...$parameters);
    }

    /**
     * Resolve the variable.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func($this->callable);
    }

    /**
     * Resolve the variable as a string.
     *
     * @return mixed
     */
    public function __toString(): string
    {
        return (string)$this->__invoke();
    }
}
