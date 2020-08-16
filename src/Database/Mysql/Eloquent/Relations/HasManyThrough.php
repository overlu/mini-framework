<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Generator;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Pagination\LengthAwarePaginator;
use Mini\Contracts\Pagination\Paginator;
use Mini\Contracts\Support\Arrayable;
use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Collection;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\ModelNotFoundException;
use Mini\Database\Mysql\Eloquent\SoftDeletes;

class HasManyThrough extends Relation
{
    /**
     * The "through" parent model instance.
     *
     * @var Model
     */
    protected Model $throughParent;

    /**
     * The far parent model instance.
     *
     * @var Model
     */
    protected Model $farParent;

    /**
     * The near key on the relationship.
     *
     * @var string
     */
    protected string $firstKey;

    /**
     * The far key on the relationship.
     *
     * @var string
     */
    protected string $secondKey;

    /**
     * The local key on the relationship.
     *
     * @var string
     */
    protected string $localKey;

    /**
     * The local key on the intermediary model.
     *
     * @var string
     */
    protected string $secondLocalKey;

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static int $selfJoinCount = 0;

    /**
     * Create a new has many through relationship instance.
     *
     * @param Builder $query
     * @param Model $farParent
     * @param Model $throughParent
     * @param string $firstKey
     * @param string $secondKey
     * @param string $localKey
     * @param string $secondLocalKey
     * @return void
     */
    public function __construct(Builder $query, Model $farParent, Model $throughParent, string $firstKey, string $secondKey, string $localKey, string $secondLocalKey)
    {
        $this->localKey = $localKey;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->farParent = $farParent;
        $this->throughParent = $throughParent;
        $this->secondLocalKey = $secondLocalKey;

        parent::__construct($query, $throughParent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        $localValue = $this->farParent[$this->localKey];

        $this->performJoin();

        if (static::$constraints) {
            $this->query->where($this->getQualifiedFirstKeyName(), '=', $localValue);
        }
    }

    /**
     * Set the join clause on the query.
     *
     * @param Builder|null $query
     * @return void
     */
    protected function performJoin(?Builder $query = null): void
    {
        $query = $query ?: $this->query;

        $farKey = $this->getQualifiedFarKeyName();

        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $farKey);

        if ($this->throughParentSoftDeletes()) {
            $query->withGlobalScope('SoftDeletableHasManyThrough', function ($query) {
                $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
            });
        }
    }

    /**
     * Get the fully qualified parent key name.
     *
     * @return string
     */
    public function getQualifiedParentKeyName(): string
    {
        return $this->parent->qualifyColumn($this->secondLocalKey);
    }

    /**
     * Determine whether "through" parent of the relation uses Soft Deletes.
     *
     * @return bool
     */
    public function throughParentSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->throughParent), true);
    }

    /**
     * Indicate that trashed "through" parents should be included in the query.
     *
     * @return $this
     */
    public function withTrashedParents(): self
    {
        $this->query->withoutGlobalScope('SoftDeletableHasManyThrough');

        return $this;
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        $whereIn = $this->whereInMethod($this->farParent, $this->localKey);

        $this->query->{$whereIn}(
            $this->getQualifiedFirstKeyName(), $this->getKeys($models, $this->localKey)
        );
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
            $model->setRelation($relation, $this->related->newCollection());
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
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                $model->setRelation(
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        foreach ($results as $result) {
            $dictionary[$result->laravel_through_key][] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * @param array $attributes
     * @return Model
     */
    public function firstOrNew(array $attributes = []): Model
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->related->newInstance($attributes);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return Model
     */
    public function updateOrCreate(array $attributes = [], array $values = []): Model
    {
        $instance = $this->firstOrNew($attributes);

        $instance->fill($values)->save();

        return $instance;
    }

    /**
     * Add a basic where clause to the query, and return the first result.
     *
     * @param \Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return Model|static
     */
    public function firstWhere($column, $operator = null, $value = null, string $boolean = 'and')
    {
        return $this->where($column, $operator, $value, $boolean)->first();
    }

    /**
     * Execute the query and get the first related model.
     *
     * @param array $columns
     * @return mixed
     */
    public function first(array $columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $columns
     * @return Model|static
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(array $columns = ['*'])
    {
        if (!is_null($model = $this->first($columns))) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->related));
    }

    /**
     * Find a related model by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|Collection|null
     */
    public function find($id, array $columns = ['*'])
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        return $this->where(
            $this->getRelated()->getQualifiedKeyName(), '=', $id
        )->first($columns);
    }

    /**
     * Find multiple related models by their primary keys.
     *
     * @param Arrayable|array $ids
     * @param array $columns
     * @return Collection
     */
    public function findMany($ids, array $columns = ['*']): Collection
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if (empty($ids)) {
            return $this->getRelated()->newCollection();
        }

        return $this->whereIn(
            $this->getRelated()->getQualifiedKeyName(), $ids
        )->get($columns);
    }

    /**
     * Find a related model by its primary key or throw an exception.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|Collection
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, array $columns = ['*'])
    {
        $result = $this->find($id, $columns);

        $id = $id instanceof Arrayable ? $id->toArray() : $id;

        if (is_array($id)) {
            if (count($result) === count(array_unique($id))) {
                return $result;
            }
        } elseif (!is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->related), $id);
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return !is_null($this->farParent->{$this->localKey})
            ? $this->get()
            : $this->related->newCollection();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     * @return Collection
     */
    public function get(array $columns = ['*']): Collection
    {
        $builder = $this->prepareQueryBuilder($columns);

        $models = $builder->getModels();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int $page
     * @return LengthAwarePaginator
     * @throws BindingResolutionException
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return $this->query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return Paginator
     * @throws BindingResolutionException
     */
    public function simplePaginate(?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return $this->query->simplePaginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Set the select clause for the relation query.
     *
     * @param array $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*']): array
    {
        if ($columns === ['*']) {
            $columns = [$this->related->getTable() . '.*'];
        }

        return array_merge($columns, [$this->getQualifiedFirstKeyName() . ' as laravel_through_key']);
    }

    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     * @return bool
     */
    public function chunk(int $count, callable $callback): bool
    {
        return $this->prepareQueryBuilder()->chunk($count, $callback);
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * @param int $count
     * @param callable $callback
     * @param string|null $column
     * @param string|null $alias
     * @return bool
     */
    public function chunkById(int $count, callable $callback, ?string $column = null, ?string $alias = null): bool
    {
        $column = $column ?? $this->getRelated()->getQualifiedKeyName();

        $alias = $alias ?? $this->getRelated()->getKeyName();

        return $this->prepareQueryBuilder()->chunkById($count, $callback, $column, $alias);
    }

    /**
     * Get a generator for the given query.
     *
     * @return Generator|mixed
     */
    public function cursor()
    {
        return $this->prepareQueryBuilder()->cursor();
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param callable $callback
     * @param int $count
     * @return bool
     */
    public function each(callable $callback, int $count = 1000): bool
    {
        return $this->chunk($count, static function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Prepare the query builder for query execution.
     *
     * @param array $columns
     * @return Builder
     */
    protected function prepareQueryBuilder(array $columns = ['*']): Builder
    {
        $builder = $this->query->applyScopes();

        return $builder->addSelect(
            $this->shouldSelect($builder->getQuery()->columns ? [] : $columns)
        );
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param Builder $query
     * @param Builder $parentQuery
     * @param array|mixed $columns
     * @return Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        if ($parentQuery->getQuery()->from === $this->throughParent->getTable()) {
            return $this->getRelationExistenceQueryForThroughSelfRelation($query, $parentQuery, $columns);
        }

        $this->performJoin($query);

        return $query->select($columns)->whereColumn(
            $this->getQualifiedLocalKeyName(), '=', $this->getQualifiedFirstKeyName()
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
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, array $columns = ['*']): Builder
    {
        $query->from($query->getModel()->getTable() . ' as ' . $hash = $this->getRelationCountHash());

        $query->join($this->throughParent->getTable(), $this->getQualifiedParentKeyName(), '=', $hash . '.' . $this->secondKey);

        if ($this->throughParentSoftDeletes()) {
            $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
        }

        $query->getModel()->setTable($hash);

        return $query->select($columns)->whereColumn(
            $parentQuery->getQuery()->from . '.' . $this->localKey, '=', $this->getQualifiedFirstKeyName()
        );
    }

    /**
     * Add the constraints for a relationship query on the same table as the through parent.
     *
     * @param Builder $query
     * @param Builder $parentQuery
     * @param array|mixed $columns
     * @return Builder
     */
    public function getRelationExistenceQueryForThroughSelfRelation(Builder $query, Builder $parentQuery, array $columns = ['*']): Builder
    {
        $table = $this->throughParent->getTable() . ' as ' . $hash = $this->getRelationCountHash();

        $query->join($table, $hash . '.' . $this->secondLocalKey, '=', $this->getQualifiedFarKeyName());

        if ($this->throughParentSoftDeletes()) {
            $query->whereNull($hash . '.' . $this->throughParent->getDeletedAtColumn());
        }

        return $query->select($columns)->whereColumn(
            $parentQuery->getQuery()->from . '.' . $this->localKey, '=', $hash . '.' . $this->firstKey
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
     * Get the qualified foreign key on the related model.
     *
     * @return string
     */
    public function getQualifiedFarKeyName(): string
    {
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get the foreign key on the "through" model.
     *
     * @return string
     */
    public function getFirstKeyName(): string
    {
        return $this->firstKey;
    }

    /**
     * Get the qualified foreign key on the "through" model.
     *
     * @return string
     */
    public function getQualifiedFirstKeyName(): string
    {
        return $this->throughParent->qualifyColumn($this->firstKey);
    }

    /**
     * Get the foreign key on the related model.
     *
     * @return string
     */
    public function getForeignKeyName(): string
    {
        return $this->secondKey;
    }

    /**
     * Get the qualified foreign key on the related model.
     *
     * @return string
     */
    public function getQualifiedForeignKeyName(): string
    {
        return $this->related->qualifyColumn($this->secondKey);
    }

    /**
     * Get the local key on the far parent model.
     *
     * @return string
     */
    public function getLocalKeyName(): string
    {
        return $this->localKey;
    }

    /**
     * Get the qualified local key on the far parent model.
     *
     * @return string
     */
    public function getQualifiedLocalKeyName(): string
    {
        return $this->farParent->qualifyColumn($this->localKey);
    }

    /**
     * Get the local key on the intermediary model.
     *
     * @return string
     */
    public function getSecondLocalKeyName(): string
    {
        return $this->secondLocalKey;
    }
}
