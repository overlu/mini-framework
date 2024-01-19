<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use RuntimeException;

class RelationNotFoundException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public string $model;

    /**
     * The name of the relation.
     *
     * @var string
     */
    public string $relation;

    /**
     * Create a new exception instance.
     *
     * @param object $model
     * @param string $relation
     * @return static
     */
    public static function make(object $model, string $relation): static
    {
        $class = get_class($model);

        $instance = new static("Call to undefined relationship [{$relation}] on model [{$class}].");

        $instance->model = $class;
        $instance->relation = $relation;

        return $instance;
    }
}
