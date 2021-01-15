<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Model;

abstract class MorphOneOrMany extends HasOneOrMany
{
    /**
     * The foreign key type for the relationship.
     *
     * @var string
     */
    protected string $morphType;

    /**
     * The class name of the parent model.
     *
     * @var string
     */
    protected string $morphClass;

    /**
     * Create a new morph one or many relationship instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
=======
     * @param Builder $query
     * @param Model $parent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, string $type, string $id, string $localKey)
    {
        $this->morphType = $type;

        $this->morphClass = $parent->getMorphClass();

        parent::__construct($query, $parent, $id, $localKey);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            parent::addConstraints();

            $this->query->where($this->morphType, $this->morphClass);
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        parent::addEagerConstraints($models);

        $this->query->where($this->morphType, $this->morphClass);
    }

    /**
     * Set the foreign ID and type for creating a related model.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model $model
=======
     * @param Model $model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model): void
    {
        $model->{$this->getForeignKeyName()} = $this->getParentKey();

        $model->{$this->getMorphType()} = $this->morphClass;
    }

    /**
     * Get the relationship query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Builder $parentQuery
     * @param array|mixed $columns
     * @return \Mini\Database\Mysql\Eloquent\Builder
=======
     * @param Builder $query
     * @param Builder $parentQuery
     * @param array|mixed $columns
     * @return Builder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $query->qualifyColumn($this->getMorphType()), $this->morphClass
        );
    }

    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getQualifiedMorphType(): string
    {
        return $this->morphType;
    }

    /**
     * Get the plain morph type name without the table.
     *
     * @return string
     */
    public function getMorphType(): string
    {
        return last(explode('.', $this->morphType));
    }

    /**
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass(): string
    {
        return $this->morphClass;
    }
}
