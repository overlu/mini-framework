<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Collection;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\Relations\Concerns\SupportsDefaultModels;

class BelongsTo extends Relation
{
    use SupportsDefaultModels;

    /**
     * The child model instance of the relation.
     *
     * @var Model
     */
    protected Model $child;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected string $foreignKey;

    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected string $ownerKey;

    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected string $relationName;

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static int $selfJoinCount = 0;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param Builder $query
     * @param Model $child
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relationName
     *
     * @return void
     */
    public function __construct(Builder $query, Model $child, string $foreignKey, string $ownerKey, string $relationName)
    {
        $this->ownerKey = $ownerKey;
        $this->relationName = $relationName;
        $this->foreignKey = $foreignKey;

        // In the underlying base relationship class, this variable is referred to as
        // the "parent" since most relationships are not inversed. But, since this
        // one is we will create a "child" variable for much better readability.
        $this->child = $child;

        parent::__construct($query, $child);
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults(): mixed
    {
        if (is_null($this->child->{$this->foreignKey})) {
            return $this->getDefaultFor($this->parent);
        }

        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $table = $this->related->getTable();

            $this->query->where($table . '.' . $this->ownerKey, '=', $this->child->{$this->foreignKey});
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
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
        $key = $this->related->getTable() . '.' . $this->ownerKey;

        $whereIn = $this->whereInMethod($this->related, $this->ownerKey);

        $this->query->{$whereIn}($key, $this->getEagerModelKeys($models));
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param array $models
     * @return array
     */
    protected function getEagerModelKeys(array $models): array
    {
        $keys = [];

        // First we need to gather all of the keys from the parent models so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
        foreach ($models as $model) {
            if (!is_null($value = $model->{$this->foreignKey})) {
                $keys[] = $value;
            }
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param array $models
     * @param string $relation
     * @return array
     */
    public function initRelation(array $models, string $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            if (isset($dictionary[$model->{$foreign}])) {
                $model->setRelation($relation, $dictionary[$model->{$foreign}]);
            }
        }

        return $models;
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param int|string|Model $model
     * @return Model
     */
    public function associate(int|Model|string $model): Model
    {
        $ownerKey = $model instanceof Model ? $model->getAttribute($this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            $this->child->setRelation($this->relationName, $model);
        } else {
            $this->child->unsetRelation($this->relationName);
        }

        return $this->child;
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return Model
     */
    public function dissociate(): Model
    {
        $this->child->setAttribute($this->foreignKey, null);

        return $this->child->setRelation($this->relationName, null);
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param Builder $query
     * @param Builder $parentQuery
     * @param array|mixed $columns
     * @return Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, mixed $columns = ['*']): Builder
    {
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        return $query->select($columns)->whereColumn(
            $this->getQualifiedForeignKeyName(), '=', $query->qualifyColumn($this->ownerKey)
        );
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param Builder $query
     * @param Builder $parentQuery
     * @param array|mixed $columns
     * @return Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, mixed $columns = ['*']): Builder
    {
        $query->select($columns)->from(
            $query->getModel()->getTable() . ' as ' . $hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        return $query->whereColumn(
            $hash . '.' . $this->ownerKey, '=', $this->getQualifiedForeignKeyName()
        );
    }

    /**
     * Get a relationship join table hash.
     *
     * @return string
     */
    public function getRelationCountHash(): string
    {
        return 'mini_reserved_' . static::$selfJoinCount++;
    }

    /**
     * Determine if the related model has an auto-incrementing ID.
     *
     * @return bool
     */
    protected function relationHasIncrementingId(): bool
    {
        return $this->related->getIncrementing() &&
            in_array($this->related->getKeyType(), ['int', 'integer']);
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param Model $parent
     * @return Model
     */
    protected function newRelatedInstanceFor(Model $parent): Model
    {
        return $this->related->newInstance();
    }

    /**
     * Get the child of the relationship.
     *
     * @return Model
     */
    public function getChild(): Model
    {
        return $this->child;
    }

    /**
     * Get the foreign key of the relationship.
     *
     * @return string
     */
    public function getForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the fully qualified foreign key of the relationship.
     *
     * @return string
     */
    public function getQualifiedForeignKeyName(): string
    {
        return $this->child->qualifyColumn($this->foreignKey);
    }

    /**
     * Get the associated key of the relationship.
     *
     * @return string
     */
    public function getOwnerKeyName(): string
    {
        return $this->ownerKey;
    }

    /**
     * Get the fully qualified associated key of the relationship.
     *
     * @return string
     */
    public function getQualifiedOwnerKeyName(): string
    {
        return $this->related->qualifyColumn($this->ownerKey);
    }

    /**
     * Get the name of the relationship.
     *
     * @return string
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }
}
