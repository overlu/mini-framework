<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
namespace Mini\Database\Mysql\Eloquent;

interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Mini\Database\Mysql\Eloquent\Builder  $builder
     * @param  \Mini\Database\Mysql\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
