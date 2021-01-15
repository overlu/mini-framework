<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $builder
     * @param \Mini\Database\Mysql\Eloquent\Model $model
=======
     * @param Builder $builder
     * @param Model $model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public function apply(Builder $builder, Model $model): void;
}
