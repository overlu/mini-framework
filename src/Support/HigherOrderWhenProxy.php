<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

class HigherOrderWhenProxy
{
    /**
     * The collection being operated on.
     *
     * @var Enumerable
     */
    protected Enumerable $collection;

    /**
     * The condition for proxying.
     *
     * @var bool
     */
    protected bool $condition;

    /**
     * Create a new proxy instance.
     *
     * @param Enumerable $collection
     * @param bool $condition
     * @return void
     */
    public function __construct(Enumerable $collection, bool $condition)
    {
        $this->condition = $condition;
        $this->collection = $collection;
    }

    /**
     * Proxy accessing an attribute onto the collection.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->condition
            ? $this->collection->{$key}
            : $this->collection;
    }

    /**
     * Proxy a method call onto the collection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->condition
            ? $this->collection->{$method}(...$parameters)
            : $this->collection;
    }
}
