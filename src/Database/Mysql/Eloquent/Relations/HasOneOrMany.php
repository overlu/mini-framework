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

abstract class HasOneOrMany extends Relation
{
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected string $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected string $localKey;

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static int $selfJoinCount = 0;

    /**
     * Create a new has one or many relationship instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
=======
     * @param Builder $query
     * @param Model $parent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;

        parent::__construct($query, $parent);
    }

    /**
     * Create and return an un-saved instance of the related model.
     *
     * @param array $attributes
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function make(array $attributes = []): Model
    {
        return tap($this->related->newInstance($attributes), function ($instance) {
            $this->setForeignAttributesForCreate($instance);
        });
    }

    /**
     * Create and return an un-saved instances of the related models.
     *
     * @param iterable $records
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Collection
=======
     * @return Collection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function makeMany(iterable $records): Collection
    {
        $instances = $this->related->newCollection();

        foreach ($records as $record) {
            $instances->push($this->make($record));
        }

        return $instances;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());

            $this->query->whereNotNull($this->foreignKey);
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
        $whereIn = $this->whereInMethod($this->parent, $this->localKey);

        $this->query->{$whereIn}(
            $this->foreignKey, $this->getKeys($models, $this->localKey)
        );
    }

    /**
     * Match the eagerly loaded results to their single parents.
     *
     * @param array $models
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
=======
     * @param Collection $results
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $relation
     * @return array
     */
    public function matchOne(array $models, Collection $results, string $relation): array
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array $models
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
=======
     * @param Collection $results
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $relation
     * @return array
     */
    public function matchMany(array $models, Collection $results, string $relation): array
    {
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array $models
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
=======
     * @param Collection $results
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $relation
     * @param string $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, string $relation, string $type): array
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                $model->setRelation(
                    $relation, $this->getRelationValue($dictionary, $key, $type)
                );
            }
        }

        return $models;
    }

    /**
     * Get the value of a relationship by one or many type.
     *
     * @param array $dictionary
     * @param string $key
     * @param string $type
     * @return mixed
     */
    protected function getRelationValue(array $dictionary, string $key, string $type)
    {
        $value = $dictionary[$key];

        return $type === 'one' ? reset($value) : $this->related->newCollection($value);
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
=======
     * @param Collection $results
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected function buildDictionary(Collection $results): array
    {
        $foreign = $this->getForeignKeyName();

        return $results->mapToDictionary(static function ($result) use ($foreign) {
            return [$result->{$foreign} => $result];
        })->all();
    }

    /**
     * Find a model by its primary key or return new instance of the related model.
     *
     * @param mixed $id
     * @param array $columns
<<<<<<< HEAD
     * @return \Mini\Support\Collection|\Mini\Database\Mysql\Eloquent\Model
=======
     * @return \Mini\Support\Collection|Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function findOrNew($id, array $columns = ['*'])
    {
        if (is_null($instance = $this->find($id, $columns))) {
            $instance = $this->related->newInstance();

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * @param array $attributes
<<<<<<< HEAD
     * @param array $values
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function firstOrNew(array $attributes = []): Model
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->related->newInstance($attributes);

            $this->setForeignAttributesForCreate($instance);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
     *
     * @param array $attributes
<<<<<<< HEAD
     * @param array $values
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function firstOrCreate(array $attributes = []): Model
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->create($attributes);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function updateOrCreate(array $attributes = [], array $values = []): Model
    {
        return tap($this->firstOrNew($attributes), static function ($instance) use ($values) {
            $instance->fill($values);

            $instance->save();
        });
    }

    /**
     * Attach a model instance to the parent model.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model $model
     * @return \Mini\Database\Mysql\Eloquent\Model|false
=======
     * @param Model $model
     * @return Model|false
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function save(Model $model)
    {
        $this->setForeignAttributesForCreate($model);

        return $model->save() ? $model : false;
    }

    /**
     * Attach a collection of models to the parent instance.
     *
     * @param iterable $models
     * @return iterable
     */
    public function saveMany(iterable $models): iterable
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    /**
     * Create a new instance of the related model.
     *
     * @param array $attributes
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function create(array $attributes = []): Model
    {
        return tap($this->related->newInstance($attributes), function ($instance) {
            $this->setForeignAttributesForCreate($instance);

            $instance->save();
        });
    }

    /**
     * Create a Collection of new instances of the related model.
     *
     * @param iterable $records
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Collection
=======
     * @return Collection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function createMany(iterable $records): Collection
    {
        $instances = $this->related->newCollection();

        foreach ($records as $record) {
            $instances->push($this->create($record));
        }

        return $instances;
    }

    /**
     * Set the foreign ID for creating a related model.
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
        $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
    }

    /**
     * Add the constraints for a relationship query.
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
        if ($query->getQuery()->from === $parentQuery->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Add the constraints for a relationship query on the same table.
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
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, array $columns = ['*']): Builder
    {
        $query->from($query->getModel()->getTable() . ' as ' . $hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        return $query->select($columns)->whereColumn(
            $this->getQualifiedParentKeyName(), '=', $hash . '.' . $this->getForeignKeyName()
        );
    }

    /**
     * Get a relationship join table hash.
     *
     * @return string
     */
    public function getRelationCountHash(): string
    {
        return 'laravel_reserved_' . static::$selfJoinCount++;
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getExistenceCompareKey(): string
    {
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get the key value of the parent's local key.
     *
     * @return mixed
     */
    public function getParentKey()
    {
        return $this->parent->getAttribute($this->localKey);
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName(): string
    {
        return $this->parent->qualifyColumn($this->localKey);
    }

    /**
     * Get the plain foreign key.
     *
     * @return string
     */
    public function getForeignKeyName(): string
    {
        $segments = explode('.', $this->getQualifiedForeignKeyName());

        return end($segments);
    }

    /**
     * Get the foreign key for the relationship.
     *
     * @return string
     */
    public function getQualifiedForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    /**
     * Get the local key for the relationship.
     *
     * @return string
     */
    public function getLocalKeyName(): string
    {
        return $this->localKey;
    }
}
