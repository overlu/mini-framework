<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

/**
 * @mixin Collection
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 */
class HigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * @var Collection
     */
    protected Collection $collection;

    /**
     * The method being proxied.
     *
     * @var string
     */
    protected string $method;

    /**
     * Create a new proxy instance.
     * @param Collection $collection
     * @param string $method
     */
    public function __construct(Collection $collection, string $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * Proxy accessing an attribute onto the collection items.
     * @param string $key
     * @return
     */
    public function __get(string $key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items.
     * @param string $method
     * @param array $parameters
     * @return
     */
    public function __call(string $method, array $parameters)
    {
        return $this->collection->{$this->method}(static function ($value) use ($method, $parameters) {
            return $value->{$method}(...$parameters);
        });
    }
}
