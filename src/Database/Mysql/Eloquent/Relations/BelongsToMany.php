<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Closure;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Pagination\LengthAwarePaginator;
use Mini\Contracts\Pagination\Paginator;
use Mini\Contracts\Support\Arrayable;
use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Collection;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\ModelNotFoundException;
use Mini\Support\LazyCollection;
use Mini\Support\Str;
use InvalidArgumentException;

class BelongsToMany extends Relation
{
    use Concerns\InteractsWithPivotTable;

    /**
     * The intermediate table for the relation.
     *
     * @var string
     */
    protected string $table;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected string $foreignPivotKey;

    /**
     * The associated key of the relation.
     *
     * @var string
     */
    protected string $relatedPivotKey;

    /**
     * The key name of the parent model.
     *
     * @var string
     */
    protected string $parentKey;

    /**
     * The key name of the related model.
     *
     * @var string
     */
    protected string $relatedKey;

    /**
     * The "name" of the relationship.
     *
     * @var string
     */
    protected ?string $relationName;

    /**
     * The pivot table columns to retrieve.
     *
     * @var array
     */
    protected array $pivotColumns = [];

    /**
     * Any pivot table restrictions for where clauses.
     *
     * @var array
     */
    protected array $pivotWheres = [];

    /**
     * Any pivot table restrictions for whereIn clauses.
     *
     * @var array
     */
    protected array $pivotWhereIns = [];

    /**
     * Any pivot table restrictions for whereNull clauses.
     *
     * @var array
     */
    protected array $pivotWhereNulls = [];

    /**
     * The default values for the pivot columns.
     *
     * @var array
     */
    protected array $pivotValues = [];

    /**
     * Indicates if timestamps are available on the pivot table.
     *
     * @var bool
     */
    public bool $withTimestamps = false;

    /**
     * The custom pivot table column for the created_at timestamp.
     *
     * @var string
     */
    protected string $pivotCreatedAt;

    /**
     * The custom pivot table column for the updated_at timestamp.
     *
     * @var string
     */
    protected string $pivotUpdatedAt;

    /**
     * The class name of the custom pivot model to use for the relationship.
     *
     * @var string
     */
    protected string $using;

    /**
     * The name of the accessor to use for the "pivot" relationship.
     *
     * @var string
     */
    protected string $accessor = 'pivot';

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static int $selfJoinCount = 0;

    /**
     * Create a new belongs to many relationship instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
=======
     * @param Builder $query
     * @param Model $parent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param string|null $relationName
     * @return void
     */
    public function __construct(Builder $query, Model $parent, string $table, string $foreignPivotKey,
                                string $relatedPivotKey, string $parentKey, string $relatedKey, ?string $relationName = null)
    {
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->relationName = $relationName;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->table = $this->resolveTableName($table);

        parent::__construct($query, $parent);
    }

    /**
     * Attempt to resolve the intermediate table name from the given string.
     *
     * @param string $table
     * @return string
     */
    protected function resolveTableName(string $table): string
    {
        if (!Str::contains($table, '\\') || !class_exists($table)) {
            return $table;
        }

        $model = new $table;

        if (!$model instanceof Model) {
            return $table;
        }

        if ($model instanceof Pivot) {
            $this->using($table);
        }

        return $model->getTable();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        $this->performJoin();

        if (static::$constraints) {
            $this->addWhereConstraints();
        }
    }

    /**
     * Set the join clause for the relation query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder|null $query
=======
     * @param Builder|null $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return $this
     */
    protected function performJoin(?Builder $query = null): self
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $baseTable = $this->related->getTable();

        $key = $baseTable . '.' . $this->relatedKey;

        $query->join($this->table, $key, '=', $this->getQualifiedRelatedPivotKeyName());

        return $this;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints(): self
    {
        $this->query->where(
            $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
        );

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
        $whereIn = $this->whereInMethod($this->parent, $this->parentKey);

        $this->query->{$whereIn}(
            $this->getQualifiedForeignPivotKeyName(),
            $this->getKeys($models, $this->parentKey)
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
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
=======
     * @param Collection $results
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, string $relation): array
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->parentKey}])) {
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
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
=======
     * @param Collection $results
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected function buildDictionary(Collection $results): array
    {
        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->accessor}->{$this->foreignPivotKey}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the class being used for pivot models.
     *
     * @return string
     */
    public function getPivotClass(): string
    {
        return $this->using ?? Pivot::class;
    }

    /**
     * Specify the custom pivot model to use for the relationship.
     *
     * @param string $class
     * @return $this
     */
    public function using(string $class): self
    {
        $this->using = $class;

        return $this;
    }

    /**
     * Specify the custom pivot accessor to use for the relationship.
     *
     * @param string $accessor
     * @return $this
     */
    public function as(string $accessor): self
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * Set a where clause for a pivot table column.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     */
    public function wherePivot(string $column, $operator = null, $value = null, string $boolean = 'and'): self
    {
        $this->pivotWheres[] = func_get_args();

        return $this->where($this->table . '.' . $column, $operator, $value, $boolean);
    }

    /**
     * Set a "where between" clause for a pivot table column.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function wherePivotBetween(string $column, array $values, string $boolean = 'and', bool $not = false): self
    {
        return $this->whereBetween($this->table . '.' . $column, $values, $boolean, $not);
    }

    /**
     * Set a "or where between" clause for a pivot table column.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWherePivotBetween(string $column, array $values): self
    {
        return $this->wherePivotBetween($column, $values, 'or');
    }

    /**
     * Set a "where pivot not between" clause for a pivot table column.
     *
     * @param string $column
     * @param array $values
     * @param string $boolean
     * @return $this
     */
    public function wherePivotNotBetween(string $column, array $values, string $boolean = 'and'): self
    {
        return $this->wherePivotBetween($column, $values, $boolean, true);
    }

    /**
     * Set a "or where not between" clause for a pivot table column.
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function orWherePivotNotBetween(string $column, array $values): self
    {
        return $this->wherePivotBetween($column, $values, 'or', true);
    }

    /**
     * Set a "where in" clause for a pivot table column.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function wherePivotIn(string $column, $values, string $boolean = 'and', bool $not = false): self
    {
        $this->pivotWhereIns[] = func_get_args();

        return $this->whereIn($this->table . '.' . $column, $values, $boolean, $not);
    }

    /**
     * Set an "or where" clause for a pivot table column.
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return $this
     */
    public function orWherePivot(string $column, $operator = null, $value = null): self
    {
        return $this->wherePivot($column, $operator, $value, 'or');
    }

    /**
     * Set a where clause for a pivot table column.
     *
     * In addition, new pivot records will receive this value.
     *
     * @param string|array $column
     * @param mixed $value
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function withPivotValue($column, $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $name => $value) {
                $this->withPivotValue($name, $value);
            }

            return $this;
        }

        if (is_null($value)) {
            throw new InvalidArgumentException('The provided value may not be null.');
        }

        $this->pivotValues[] = compact('column', 'value');

        return $this->wherePivot($column, '=', $value);
    }

    /**
     * Set an "or where in" clause for a pivot table column.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function orWherePivotIn(string $column, $values): self
    {
        return $this->wherePivotIn($column, $values, 'or');
    }

    /**
     * Set a "where not in" clause for a pivot table column.
     *
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @return $this
     */
    public function wherePivotNotIn(string $column, $values, string $boolean = 'and'): self
    {
        return $this->wherePivotIn($column, $values, $boolean, true);
    }

    /**
     * Set an "or where not in" clause for a pivot table column.
     *
     * @param string $column
     * @param mixed $values
     * @return $this
     */
    public function orWherePivotNotIn(string $column, $values): self
    {
        return $this->wherePivotNotIn($column, $values, 'or');
    }

    /**
     * Set a "where null" clause for a pivot table column.
     *
     * @param string $column
     * @param string $boolean
     * @param bool $not
     * @return $this
     */
    public function wherePivotNull(string $column, string $boolean = 'and', bool $not = false): self
    {
        $this->pivotWhereNulls[] = func_get_args();

        return $this->whereNull($this->table . '.' . $column, $boolean, $not);
    }

    /**
     * Set a "where not null" clause for a pivot table column.
     *
     * @param string $column
     * @param string $boolean
     * @return $this
     */
    public function wherePivotNotNull(string $column, string $boolean = 'and'): self
    {
        return $this->wherePivotNull($column, $boolean, true);
    }

    /**
     * Set a "or where null" clause for a pivot table column.
     *
     * @param string $column
     * @param bool $not
     * @return $this
     */
    public function orWherePivotNull(string $column, bool $not = false): self
    {
        return $this->wherePivotNull($column, 'or', $not);
    }

    /**
     * Set a "or where not null" clause for a pivot table column.
     *
     * @param string $column
<<<<<<< HEAD
     * @param bool $not
=======
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return $this
     */
    public function orWherePivotNotNull(string $column): self
    {
        return $this->orWherePivotNull($column, true);
    }

    /**
     * Find a related model by its primary key or return new instance of the related model.
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
        }

        return $instance;
    }

    /**
     * Get the first related model record matching the attributes or instantiate it.
     *
     * @param array $attributes
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function firstOrNew(array $attributes = []): Model
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->related->newInstance($attributes);
        }

        return $instance;
    }

    /**
     * Get the first related record matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $joining
     * @param bool $touch
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function firstOrCreate(array $attributes = [], array $joining = [], bool $touch = true): Model
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->create($attributes, $joining, $touch);
        }

        return $instance;
    }

    /**
     * Create or update a related record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @param array $joining
     * @param bool $touch
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function updateOrCreate(array $attributes = [], array $values = [], array $joining = [], bool $touch = true): Model
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            return $this->create($values, $joining, $touch);
        }

        $instance->fill($values);

        $instance->save(['touch' => false]);

        return $instance;
    }

    /**
     * Find a related model by its primary key.
     *
     * @param mixed $id
     * @param array $columns
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model|\Mini\Database\Mysql\Eloquent\Collection|null
=======
     * @return Model|Collection|null
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function find($id, array $columns = ['*'])
    {
        if (!$id instanceof Model && (is_array($id) || $id instanceof Arrayable)) {
            return $this->findMany($id, $columns);
        }

        return $this->where(
            $this->getRelated()->getQualifiedKeyName(), '=', $this->parseId($id)
        )->first($columns);
    }

    /**
     * Find multiple related models by their primary keys.
     *
<<<<<<< HEAD
     * @param \Mini\Contracts\Support\Arrayable|array $ids
     * @param array $columns
     * @return \Mini\Database\Mysql\Eloquent\Collection
=======
     * @param Arrayable|array $ids
     * @param array $columns
     * @return Collection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function findMany($ids, array $columns = ['*']): Collection
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if (empty($ids)) {
            return $this->getRelated()->newCollection();
        }

        return $this->whereIn(
            $this->getRelated()->getQualifiedKeyName(), $this->parseIds($ids)
        )->get($columns);
    }

    /**
     * Find a related model by its primary key or throw an exception.
     *
     * @param mixed $id
     * @param array $columns
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model|\Mini\Database\Mysql\Eloquent\Collection
=======
     * @return Model|Collection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
     * Add a basic where clause to the query, and return the first result.
     *
<<<<<<< HEAD
     * @param \Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return \Mini\Database\Mysql\Eloquent\Model|static
=======
     * @param Closure|string|array $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return Model|static
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function firstWhere($column, $operator = null, $value = null, string $boolean = 'and')
    {
        return $this->where($column, $operator, $value, $boolean)->first();
    }

    /**
     * Execute the query and get the first result.
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
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model|static
=======
     * @return Model|static
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return !is_null($this->parent->{$this->parentKey})
            ? $this->get()
            : $this->related->newCollection();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Collection
=======
     * @return Collection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function get(array $columns = ['*']): Collection
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $builder = $this->query->applyScopes();

        $columns = $builder->getQuery()->columns ? [] : $columns;

        $models = $builder->addSelect(
            $this->shouldSelect($columns)
        )->getModels();

        $this->hydratePivotRelation($models);

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    /**
     * Get the select columns for the relation query.
     *
     * @param array $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*']): array
    {
<<<<<<< HEAD
        if ($columns == ['*']) {
=======
        if ($columns === ['*']) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            $columns = [$this->related->getTable() . '.*'];
        }

        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * Get the pivot columns for the relation.
     *
     * "pivot_" is prefixed ot each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns(): array
    {
        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey];

        return collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $this->table . '.' . $column . ' as pivot_' . $column;
        })->unique()->all();
    }

    /**
     * Get a paginator for the "select" statement.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
<<<<<<< HEAD
     * @return \Mini\Contracts\Pagination\LengthAwarePaginator
=======
     * @return LengthAwarePaginator
     * @throws BindingResolutionException
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return tap($this->query->paginate($perPage, $columns, $pageName, $page), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());
        });
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
<<<<<<< HEAD
     * @return \Mini\Contracts\Pagination\Paginator
=======
     * @return Paginator
     * @throws BindingResolutionException
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function simplePaginate(?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator
    {
        $this->query->addSelect($this->shouldSelect($columns));

        return tap($this->query->simplePaginate($perPage, $columns, $pageName, $page), function ($paginator) {
            $this->hydratePivotRelation($paginator->items());
        });
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
        $this->query->addSelect($this->shouldSelect());

        return $this->query->chunk($count, function ($results) use ($callback) {
            $this->hydratePivotRelation($results->all());

            return $callback($results);
        });
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
        $this->query->addSelect($this->shouldSelect());

        $column = $column ?? $this->getRelated()->qualifyColumn(
                $this->getRelatedKeyName()
            );

        $alias = $alias ?? $this->getRelatedKeyName();

        return $this->query->chunkById($count, function ($results) use ($callback) {
            $this->hydratePivotRelation($results->all());

            return $callback($results);
        }, $column, $alias);
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
     * Get a lazy collection for the given query.
     *
     * @return LazyCollection
     */
    public function cursor(): LazyCollection
    {
        $this->query->addSelect($this->shouldSelect());

        return $this->query->cursor()->map(function ($model) {
            $this->hydratePivotRelation([$model]);

            return $model;
        });
    }

    /**
     * Hydrate the pivot table relationship on the models.
     *
     * @param array $models
     * @return void
     */
    protected function hydratePivotRelation(array $models): void
    {
        // To hydrate the pivot relationship, we will just gather the pivot attributes
        // and create a new Pivot model, which is basically a dynamic model that we
        // will set the attributes, table, and connections on it so it will work.
        foreach ($models as $model) {
            $model->setRelation($this->accessor, $this->newExistingPivot(
                $this->migratePivotAttributes($model)
            ));
        }
    }

    /**
     * Get the pivot attributes from a model.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model $model
=======
     * @param Model $model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected function migratePivotAttributes(Model $model): array
    {
        $values = [];

        foreach ($model->getAttributes() as $key => $value) {
            // To get the pivots attributes we will just take any of the attributes which
            // begin with "pivot_" and add those to this arrays, as well as unsetting
            // them from the parent's models since they exist in a different table.
            if (strpos($key, 'pivot_') === 0) {
                $values[substr($key, 6)] = $value;

                unset($model->$key);
            }
        }

        return $values;
    }

    /**
     * If we're touching the parent model, touch.
     *
     * @return void
     */
    public function touchIfTouching(): void
    {
        if ($this->touchingParent()) {
            $this->getParent()->touch();
        }

        if ($this->getParent()->touches($this->relationName)) {
            $this->touch();
        }
    }

    /**
     * Determine if we should touch the parent on sync.
     *
     * @return bool
     */
    protected function touchingParent(): bool
    {
        return $this->getRelated()->touches($this->guessInverseRelation());
    }

    /**
     * Attempt to guess the name of the inverse of the relation.
     *
     * @return string
     */
    protected function guessInverseRelation(): string
    {
        return Str::camel(Str::pluralStudly(class_basename($this->getParent())));
    }

    /**
     * Touch all of the related models for the relationship.
     *
     * E.g.: Touch all roles associated with this user.
     *
     * @return void
     */
    public function touch(): void
    {
        $key = $this->getRelated()->getKeyName();

        $columns = [
            $this->related->getUpdatedAtColumn() => $this->related->freshTimestampString(),
        ];

        // If we actually have IDs for the relation, we will run the query to update all
        // the related model's timestamps, to make sure these all reflect the changes
        // to the parent models. This will help us keep any caching synced up here.
        if (count($ids = $this->allRelatedIds()) > 0) {
            $this->getRelated()->newQueryWithoutRelationships()->whereIn($key, $ids)->update($columns);
        }
    }

    /**
     * Get all of the IDs for the related models.
     *
     * @return \Mini\Support\Collection
     */
    public function allRelatedIds(): \Mini\Support\Collection
    {
        return $this->newPivotQuery()->pluck($this->relatedPivotKey);
    }

    /**
     * Save a new model and attach it to the parent model.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model $model
     * @param array $pivotAttributes
     * @param bool $touch
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @param Model $model
     * @param array $pivotAttributes
     * @param bool $touch
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function save(Model $model, array $pivotAttributes = [], bool $touch = true): Model
    {
        $model->save(['touch' => false]);

        $this->attach($model, $pivotAttributes, $touch);

        return $model;
    }

    /**
     * Save an array of new models and attach them to the parent model.
     *
     * @param \Mini\Support\Collection|array $models
     * @param array $pivotAttributes
     * @return array
     */
    public function saveMany($models, array $pivotAttributes = []): array
    {
        foreach ($models as $key => $model) {
            $this->save($model, (array)($pivotAttributes[$key] ?? []), false);
        }

        $this->touchIfTouching();

        return $models;
    }

    /**
     * Create a new instance of the related model.
     *
     * @param array $attributes
     * @param array $joining
     * @param bool $touch
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function create(array $attributes = [], array $joining = [], bool $touch = true): Model
    {
        $instance = $this->related->newInstance($attributes);

        // Once we save the related model, we need to attach it to the base model via
        // through intermediate table so we'll use the existing "attach" method to
        // accomplish this which will insert the record and any more attributes.
        $instance->save(['touch' => false]);

        $this->attach($instance, $joining, $touch);

        return $instance;
    }

    /**
     * Create an array of new instances of the related models.
     *
     * @param iterable $records
     * @param array $joinings
     * @return array
     */
    public function createMany(iterable $records, array $joinings = []): array
    {
        $instances = [];

        foreach ($records as $key => $record) {
            $instances[] = $this->create($record, (array)($joinings[$key] ?? []), false);
        }

        $this->touchIfTouching();

        return $instances;
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
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfJoin($query, $parentQuery, $columns);
        }

        $this->performJoin($query);

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
    public function getRelationExistenceQueryForSelfJoin(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $query->select($columns);

        $query->from($this->related->getTable() . ' as ' . $hash = $this->getRelationCountHash());

        $this->related->setTable($hash);

        $this->performJoin($query);

        return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getExistenceCompareKey(): string
    {
        return $this->getQualifiedForeignPivotKeyName();
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
     * Specify that the pivot table has creation and update timestamps.
     *
     * @param mixed $createdAt
     * @param mixed $updatedAt
     * @return $this
     */
    public function withTimestamps(?string $createdAt = null, ?string $updatedAt = null): self
    {
        $this->withTimestamps = true;

        $this->pivotCreatedAt = $createdAt;
        $this->pivotUpdatedAt = $updatedAt;

        return $this->withPivot($this->createdAt(), $this->updatedAt());
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function createdAt(): string
    {
        return $this->pivotCreatedAt ?: $this->parent->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function updatedAt(): string
    {
        return $this->pivotUpdatedAt ?: $this->parent->getUpdatedAtColumn();
    }

    /**
     * Get the foreign key for the relation.
     *
     * @return string
     */
    public function getForeignPivotKeyName(): string
    {
        return $this->foreignPivotKey;
    }

    /**
     * Get the fully qualified foreign key for the relation.
     *
     * @return string
     */
    public function getQualifiedForeignPivotKeyName(): string
    {
        return $this->table . '.' . $this->foreignPivotKey;
    }

    /**
     * Get the "related key" for the relation.
     *
     * @return string
     */
    public function getRelatedPivotKeyName(): string
    {
        return $this->relatedPivotKey;
    }

    /**
     * Get the fully qualified "related key" for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedPivotKeyName(): string
    {
        return $this->table . '.' . $this->relatedPivotKey;
    }

    /**
     * Get the parent key for the relationship.
     *
     * @return string
     */
    public function getParentKeyName(): string
    {
        return $this->parentKey;
    }

    /**
     * Get the fully qualified parent key name for the relation.
     *
     * @return string
     */
    public function getQualifiedParentKeyName(): string
    {
        return $this->parent->qualifyColumn($this->parentKey);
    }

    /**
     * Get the related key for the relationship.
     *
     * @return string
     */
    public function getRelatedKeyName(): string
    {
        return $this->relatedKey;
    }

    /**
     * Get the intermediate table for the relationship.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the relationship name for the relationship.
     *
     * @return string
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }

    /**
     * Get the name of the pivot accessor for this relationship.
     *
     * @return string
     */
    public function getPivotAccessor(): string
    {
        return $this->accessor;
    }

    /**
     * Get the pivot columns for this relationship.
     *
     * @return array
     */
    public function getPivotColumns(): array
    {
        return $this->pivotColumns;
    }
}
