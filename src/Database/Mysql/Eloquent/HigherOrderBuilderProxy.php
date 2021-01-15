<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

/**
 * @mixin Builder
 */
class HigherOrderBuilderProxy
{
    /**
     * The collection being operated on.
     *
     * @var Builder
     */
    protected Builder $builder;

    /**
     * The method being proxied.
     *
     * @var string
     */
    protected string $method;

    /**
     * Create a new proxy instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $builder
=======
     * @param Builder $builder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $method
     * @return void
     */
    public function __construct(Builder $builder, string $method)
    {
        $this->method = $method;
        $this->builder = $builder;
    }

    /**
     * Proxy a scope call onto the query builder.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->builder->{$this->method}(
            static function ($value) use ($method, $parameters) {
                return $value->{$method}(...$parameters);
            });
    }
}
