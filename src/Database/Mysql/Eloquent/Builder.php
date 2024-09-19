<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent;

use BadMethodCallException;
use Closure;
use Exception;
use InvalidArgumentException;
use Mini\Contracts\Pagination\LengthAwarePaginator;
use Mini\Contracts\Support\Arrayable;
use Mini\Database\Mysql\Concerns\BuildsQueries;
use Mini\Database\Mysql\Eloquent\Relations\Relation;
use Mini\Database\Mysql\Query\Builder as QueryBuilder;
use Mini\Pagination\Paginator;
use Mini\Support\Arr;
use Mini\Support\LazyCollection;
use Mini\Support\Str;
use Mini\Support\Traits\ForwardsCalls;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * @property-read HigherOrderBuilderProxy $orWhere
 *
 * @mixin QueryBuilder
 */
class Builder
{
    use BuildsQueries, Concerns\QueriesRelationships, ForwardsCalls;

    /**
     * The base query builder instance.
     *
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    /**
     * The model being queried.
     *
     * @var Model|null
     */
    protected ?Model $model = null;

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected array $eagerLoad = [];

    /**
     * All of the globally registered builder macros.
     *
     * @var array
     */
    protected static array $macros = [];

    /**
     * All of the locally registered builder macros.
     *
     * @var array
     */
    protected array $localMacros = [];

    /**
     * A replacement for the typical delete function.
     *
     * @var Closure
     */
    protected Closure $onDelete;

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected array $passthru = [
        'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'toSql', 'dump', 'dd',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection', 'raw', 'getGrammar',
    ];

    /**
     * Applied global scopes.
     *
     * @var array
     */
    protected array $scopes = [];

    /**
     * Removed global scopes.
     *
     * @var array
     */
    protected array $removedScopes = [];

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param QueryBuilder $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Create and return an un-saved model instance.
     *
     * @param array $attributes
     * @return Model|static
     */
    public function make(array $attributes = []): Model|static
    {
        return $this->newModelInstance($attributes);
    }

    /**
     * Register a new global scope.
     *
     * @param string $identifier
     * @param Closure|Scope $scope
     * @return $this
     */
    public function withGlobalScope(string $identifier, Closure|Scope $scope): self
    {
        $this->scopes[$identifier] = $scope;

        if (method_exists($scope, 'extend')) {
            $scope->extend($this);
        }

        return $this;
    }

    /**
     * Remove a registered global scope.
     *
     * @param string|Scope $scope
     * @return $this
     */
    public function withoutGlobalScope(string|Scope $scope): self
    {
        if (!is_string($scope)) {
            $scope = get_class($scope);
        }

        unset($this->scopes[$scope]);

        $this->removedScopes[] = $scope;

        return $this;
    }

    /**
     * Remove all or passed registered global scopes.
     *
     * @param array|null $scopes
     * @return $this
     */
    public function withoutGlobalScopes(array $scopes = null): self
    {
        if (!is_array($scopes)) {
            $scopes = array_keys($this->scopes);
        }

        foreach ($scopes as $scope) {
            $this->withoutGlobalScope($scope);
        }

        return $this;
    }

    /**
     * Get an array of global scopes that were removed from the query.
     *
     * @return array
     */
    public function removedScopes(): array
    {
        return $this->removedScopes;
    }

    /**
     * Add a where clause on the primary key to the query.
     *
     * @param mixed $id
     * @return $this
     */
    public function whereKey(mixed $id): self
    {
        if (is_array($id) || $id instanceof Arrayable) {
            $this->query->whereIn($this->model->getQualifiedKeyName(), $id);

            return $this;
        }

        return $this->where($this->model->getQualifiedKeyName(), '=', $id);
    }

    /**
     * Add a where clause on the primary key to the query.
     *
     * @param mixed $id
     * @return $this
     */
    public function whereKeyNot(mixed $id): self
    {
        if (is_array($id) || $id instanceof Arrayable) {
            $this->query->whereNotIn($this->model->getQualifiedKeyName(), $id);

            return $this;
        }

        return $this->where($this->model->getQualifiedKeyName(), '!=', $id);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param array|string|Closure $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @param string $boolean
     * @return $this
     */
    public function where(array|string|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): self
    {
        if ($column instanceof Closure && is_null($operator)) {
            $column($query = $this->model->newQueryWithoutRelationships());

            $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
        } else {
            $this->query->where(...func_get_args());
        }

        return $this;
    }

    /**
     * Add a basic where clause to the query, and return the first result.
     *
     * @param array|string|Closure $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @param string $boolean
     * @return Model|static
     */
    public function firstWhere(array|string|Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): Model|static
    {
        return $this->where($column, $operator, $value, $boolean)->first();
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param array|string|Closure $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function orWhere(array|string|Closure $column, mixed $operator = null, mixed $value = null): self
    {
        [$value, $operator] = $this->query->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param string|null $column
     * @return $this
     */
    public function latest(string $column = null): self
    {
        if (is_null($column)) {
            $column = $this->model->getCreatedAtColumn() ?? 'created_at';
        }

        $this->query->latest($column);

        return $this;
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param string|null $column
     * @return $this
     */
    public function oldest(string $column = null): self
    {
        if (is_null($column)) {
            $column = $this->model->getCreatedAtColumn() ?? 'created_at';
        }

        $this->query->oldest($column);

        return $this;
    }

    /**
     * Create a collection of models from plain arrays.
     *
     * @param array $items
     * @return Collection
     */
    public function hydrate(array $items): Collection
    {
        $instance = $this->newModelInstance();

        return $instance->newCollection(array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder((array)$item);
        }, $items));
    }

    /**
     * Create a collection of models from a raw query.
     *
     * @param string $query
     * @param array $bindings
     * @return Collection
     */
    public function fromQuery(string $query, array $bindings = []): Collection
    {
        return $this->hydrate(
            $this->query->getConnection()->select($query, $bindings)
        );
    }

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|Collection|Builder|null
     */
    public function find(mixed $id, array $columns = ['*']): Model|Collection|null|static
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return $this->findMany($id, $columns);
        }

        return $this->whereKey($id)->first($columns);
    }

    /**
     * Find multiple models by their primary keys.
     *
     * @param array|Arrayable $ids
     * @param array $columns
     * @return Collection
     */
    public function findMany(Arrayable|array $ids, array $columns = ['*']): Collection
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if (empty($ids)) {
            return $this->model->newCollection();
        }

        return $this->whereKey($ids)->get($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|Collection|static|static[]
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Builder|Model|Collection|null|static
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

        throw (new ModelNotFoundException)->setModel(
            get_class($this->model), $id
        );
    }

    /**
     * Find a model by its primary key or return fresh model instance.
     *
     * @param mixed $id
     * @param array $columns
     * @return Builder|Model|Collection|null
     */
    public function findOrNew(mixed $id, array $columns = ['*']): Builder|Model|Collection|null|static
    {
        if (!is_null($model = $this->find($id, $columns))) {
            return $model;
        }

        return $this->newModelInstance();
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @param array $attributes
     * @param array $values
     * @return Model|static
     */
    public function firstOrNew(array $attributes = [], array $values = []): Model|static
    {
        if (!is_null($instance = $this->where($attributes)->first())) {
            return $instance;
        }

        return $this->newModelInstance($attributes + $values);
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param array $attributes
     * @param array $values
     * @return Model|static
     */
    public function firstOrCreate(array $attributes, array $values = []): Model|static
    {
        if (!is_null($instance = $this->where($attributes)->first())) {
            return $instance;
        }

        return tap($this->newModelInstance($attributes + $values), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param array $attributes
     * @param array $values
     * @return Model|static
     */
    public function updateOrCreate(array $attributes, array $values = []): Model|static
    {
        return tap($this->firstOrNew($attributes), function ($instance) use ($values) {
            $instance->fill($values)->save();
        });
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $columns
     * @return Model|static
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(mixed $columns = ['*']): Model|static
    {
        if (!is_null($model = $this->first($columns))) {
            return $model;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->model));
    }

    /**
     * Execute the query and get the first result or call a callback.
     *
     * @param array|Closure $columns
     * @param Closure|null $callback
     * @return Model|static|mixed
     */
    public function firstOr(array|Closure $columns = ['*'], Closure $callback = null): mixed
    {
        if ($columns instanceof Closure) {
            $callback = $columns;

            $columns = ['*'];
        }

        if (!is_null($model = $this->first($columns))) {
            return $model;
        }

        return $callback();
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        if ($result = $this->first([$column])) {
            return $result->{$column};
        }
        return null;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array|string $columns
     * @return Collection|static[]
     */
    public function get(mixed $columns = ['*']): Collection|array
    {
        $builder = $this->applyScopes();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->getModel()->newCollection($models);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param array|string $columns
     * @return Model[]|static[]
     */
    public function getModels(mixed $columns = ['*']): array
    {
        return $this->model->hydrate(
            $this->query->get($columns)->all()
        )->all();
    }

    /**
     * Eager load the relationships for the models.
     *
     * @param array $models
     * @return array
     */
    public function eagerLoadRelations(array $models): array
    {
        foreach ($this->eagerLoad as $name => $constraints) {
            // For nested eager loads we'll skip loading them here and they will be set as an
            // eager load on the query to retrieve the relation so that they will be eager
            // loaded on that query, because that is where they get hydrated as models.
            if (!str_contains($name, '.')) {
                $models = $this->eagerLoadRelation($models, $name, $constraints);
            }
        }

        return $models;
    }

    /**
     * Eagerly load the relationship on a set of models.
     *
     * @param array $models
     * @param string $name
     * @param Closure $constraints
     * @return array
     */
    protected function eagerLoadRelation(array $models, string $name, Closure $constraints): array
    {
        // First we will "back up" the existing where conditions on the query so we can
        // add our eager constraints. Then we will merge the wheres that were on the
        // query back to it in order that any where conditions might be specified.
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        $constraints($relation);

        // Once we have the results, we just match those back up to their parent models
        // using the relationship instance. Then we just return the finished arrays
        // of models which have been eagerly hydrated and are readied for return.
        return $relation->match(
            $relation->initRelation($models, $name),
            $relation->getEager(), $name
        );
    }

    /**
     * Get the relation instance for the given relation name.
     *
     * @param string $name
     * @return Relation
     */
    public function getRelation(string $name): Relation
    {
        // We want to run a relationship query without any constrains so that we will
        // not have to remove these where clauses manually which gets really hacky
        // and error prone. We don't want constraints because we add eager ones.
        $relation = Relation::noConstraints(function () use ($name) {
            try {
                return $this->getModel()->newInstance()->$name();
            } catch (BadMethodCallException $e) {
                throw RelationNotFoundException::make($this->getModel(), $name);
            }
        });

        $nested = $this->relationsNestedUnder($name);

        // If there are nested relationships set on the query, we will put those onto
        // the query instances so that they can be handled after this relationship
        // is loaded. In this way they will all trickle down as they are loaded.
        if (count($nested) > 0) {
            $relation->getQuery()->with($nested);
        }

        return $relation;
    }

    /**
     * Get the deeply nested relations for a given top-level relation.
     *
     * @param string $relation
     * @return array
     */
    protected function relationsNestedUnder(string $relation): array
    {
        $nested = [];

        // We are basically looking for any relationships that are nested deeper than
        // the given top-level relationship. We will just check for any relations
        // that start with the given top relations and adds them to our arrays.
        foreach ($this->eagerLoad as $name => $constraints) {
            if ($this->isNestedUnder($relation, $name)) {
                $nested[substr($name, strlen($relation . '.'))] = $constraints;
            }
        }

        return $nested;
    }

    /**
     * Determine if the relationship is nested.
     *
     * @param string $relation
     * @param string $name
     * @return bool
     */
    protected function isNestedUnder(string $relation, string $name): bool
    {
        return Str::contains($name, '.') && Str::startsWith($name, $relation . '.');
    }

    /**
     * Get a lazy collection for the given query.
     *
     * @return LazyCollection
     */
    public function cursor(): LazyCollection
    {
        return $this->applyScopes()->query->cursor()->map(function ($record) {
            return $this->newModelInstance()->newFromBuilder($record);
        });
    }

    /**
     * Add a generic "order by" clause if the query doesn't already have one.
     *
     * @return void
     */
    protected function enforceOrderBy(): void
    {
        if (empty($this->query->orders) && empty($this->query->unionOrders)) {
            $this->orderBy($this->model->getQualifiedKeyName(), 'asc');
        }
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     * @return \Mini\Support\Collection
     */
    public function pluck(string $column, string $key = null): \Mini\Support\Collection
    {
        $results = $this->toBase()->pluck($column, $key);

        // If the model has a mutator for the requested column, we will spin through
        // the results and mutate the values so that the mutated version of these
        // columns are returned as you would expect from these Eloquent models.
        if (!$this->model->hasGetMutator($column) &&
            !$this->model->hasCast($column) &&
            !in_array($column, $this->model->getDates(), true)) {
            return $results;
        }

        return $results->map(function ($value) use ($column) {
            return $this->model->newFromBuilder([$column => $value])->{$column};
        });
    }

    /**
     * Paginate the given query.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return LengthAwarePaginator
     *
     * @throws InvalidArgumentException
     */
    public function paginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', int $page = null): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = ($total = $this->toBase()->getCountForPagination())
            ? $this->forPage($page, $perPage)->get($columns)
            : $this->model->newCollection();

        return $this->paginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Mini\Contracts\Pagination\Paginator
     */
    public function simplePaginate(int $perPage = null, array $columns = ['*'], string $pageName = 'page', int $page = null): \Mini\Contracts\Pagination\Paginator
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        // Next we will set the limit and offset for this query so that when we get the
        // results we get the proper section of results. Then, we'll create the full
        // paginator instances for these results with the given page and per page.
        $this->skip(($page - 1) * $perPage)->take($perPage + 1);

        return $this->simplePaginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     * @return Model|$this
     */
    public function create(array $attributes = []): Model|static
    {
        return tap($this->newModelInstance($attributes), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Save a new model and return the instance. Allow mass-assignment.
     *
     * @param array $attributes
     * @return Model|$this
     */
    public function forceCreate(array $attributes): Model|static
    {
        return $this->model->unguarded(function () use ($attributes) {
            return $this->newModelInstance()->create($attributes);
        });
    }

    /**
     * Update a record in the database.
     *
     * @param array $values
     * @return int
     */
    public function update(array $values): int
    {
        return $this->toBase()->update($this->addUpdatedAtColumn($values));
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param string $column
     * @param float|int $amount
     * @param array $extra
     * @return int
     */
    public function increment(string $column, float|int $amount = 1, array $extra = []): int
    {
        return $this->toBase()->increment(
            $column, $amount, $this->addUpdatedAtColumn($extra)
        );
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param string $column
     * @param float|int $amount
     * @param array $extra
     * @return int
     */
    public function decrement(string $column, float|int $amount = 1, array $extra = []): int
    {
        return $this->toBase()->decrement(
            $column, $amount, $this->addUpdatedAtColumn($extra)
        );
    }

    /**
     * Add the "updated at" column to an array of values.
     *
     * @param array $values
     * @return array
     */
    protected function addUpdatedAtColumn(array $values): array
    {
        if (!$this->model->usesTimestamps() ||
            is_null($this->model->getUpdatedAtColumn())) {
            return $values;
        }

        $column = $this->model->getUpdatedAtColumn();

        $values = array_merge(
            [$column => $this->model->freshTimestampString()],
            $values
        );

        $segments = preg_split('/\s+as\s+/i', $this->query->from);

        $qualifiedColumn = end($segments) . '.' . $column;

        $values[$qualifiedColumn] = $values[$column];

        unset($values[$column]);

        return $values;
    }

    /**
     * Delete a record from the database.
     *
     * @return mixed
     */
    public function delete(): mixed
    {
        if (isset($this->onDelete)) {
            return call_user_func($this->onDelete, $this);
        }

        return $this->toBase()->delete();
    }

    /**
     * Run the default delete function on the builder.
     *
     * Since we do not apply scopes here, the row will actually be deleted.
     *
     * @return mixed
     */
    public function forceDelete(): mixed
    {
        return $this->query->delete();
    }

    /**
     * Register a replacement for the default delete function.
     *
     * @param Closure $callback
     * @return void
     */
    public function onDelete(Closure $callback): void
    {
        $this->onDelete = $callback;
    }

    /**
     * Determine if the given model has a scope.
     *
     * @param string $scope
     * @return bool
     */
    public function hasNamedScope(string $scope): bool
    {
        return $this->model && $this->model->hasNamedScope($scope);
    }

    /**
     * Call the given local model scopes.
     *
     * @param array|string $scopes
     * @return static|mixed
     */
    public function scopes(array|string $scopes): mixed
    {
        $builder = $this;

        foreach (Arr::wrap($scopes) as $scope => $parameters) {
            // If the scope key is an integer, then the scope was passed as the value and
            // the parameter list is empty, so we will format the scope name and these
            // parameters here. Then, we'll be ready to call the scope on the model.
            if (is_int($scope)) {
                [$scope, $parameters] = [$parameters, []];
            }

            // Next we'll pass the scope callback to the callScope method which will take
            // care of grouping the "wheres" properly so the logical order doesn't get
            // messed up when adding scopes. Then we'll return back out the builder.
            $builder = $builder->callNamedScope($scope, (array)$parameters);
        }

        return $builder;
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param array $values
     * @param array|string $uniqueBy
     * @param array|null $update
     * @return int
     */
    public function upsert(array $values, array|string $uniqueBy, array $update = null)
    {
        if (empty($values)) {
            return 0;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        if (is_null($update)) {
            $update = array_keys(reset($values));
        }

        return $this->toBase()->upsert(
            $this->addTimestampsToUpsertValues($this->addUniqueIdsToUpsertValues($values)),
            $uniqueBy,
            $this->addUpdatedAtToUpsertColumns($update)
        );
    }

    /**
     * Add the "updated at" column to the updated columns.
     *
     * @param array $update
     * @return array
     */
    protected function addUpdatedAtToUpsertColumns(array $update): array
    {
        if (!$this->model->usesTimestamps()) {
            return $update;
        }

        $column = $this->model->getUpdatedAtColumn();

        if (!is_null($column) &&
            !array_key_exists($column, $update) &&
            !in_array($column, $update, true)) {
            $update[] = $column;
        }

        return $update;
    }

    /**
     * Add timestamps to the inserted values.
     *
     * @param array $values
     * @return array
     */
    protected function addTimestampsToUpsertValues(array $values): array
    {
        if (!$this->model->usesTimestamps()) {
            return $values;
        }

        $timestamp = $this->model->freshTimestampString();

        $columns = array_filter([
            $this->model->getCreatedAtColumn(),
            $this->model->getUpdatedAtColumn(),
        ]);

        foreach ($columns as $column) {
            foreach ($values as &$row) {
                $row = array_merge([$column => $timestamp], $row);
            }
        }

        return $values;
    }

    /**
     * Add unique IDs to the inserted values.
     *
     * @param array $values
     * @return array
     */
    protected function addUniqueIdsToUpsertValues(array $values): array
    {
        if (!$this->model->usesUniqueIds()) {
            return $values;
        }

        foreach ($this->model->uniqueIds() as $uniqueIdAttribute) {
            foreach ($values as &$row) {
                if (!array_key_exists($uniqueIdAttribute, $row)) {
                    $row = array_merge([$uniqueIdAttribute => $this->model->newUniqueId()], $row);
                }
            }
        }

        return $values;
    }

    /**
     * Apply the scopes to the Eloquent builder instance and return it.
     *
     * @return static
     */
    public function applyScopes(): static
    {
        if (!$this->scopes) {
            return $this;
        }

        $builder = clone $this;

        foreach ($this->scopes as $identifier => $scope) {
            if (!isset($builder->scopes[$identifier])) {
                continue;
            }

            $builder->callScope(function (self $builder) use ($scope) {
                // If the scope is a Closure we will just go ahead and call the scope with the
                // builder instance. The "callScope" method will properly group the clauses
                // that are added to this query so "where" clauses maintain proper logic.
                if ($scope instanceof Closure) {
                    $scope($builder);
                }

                // If the scope is a scope object, we will call the apply method on this scope
                // passing in the builder and the model instance. After we run all of these
                // scopes we will return back the builder instance to the outside caller.
                if ($scope instanceof Scope) {
                    $scope->apply($builder, $this->getModel());
                }
            });
        }

        return $builder;
    }

    /**
     * Apply the given scope on the current builder instance.
     *
     * @param callable $scope
     * @param array $parameters
     * @return mixed
     */
    protected function callScope(callable $scope, array $parameters = []): mixed
    {
        array_unshift($parameters, $this);

        $query = $this->getQuery();

        // We will keep track of how many wheres are on the query before running the
        // scope so that we can properly group the added scope constraints in the
        // query as their own isolated nested where statement and avoid issues.
        $originalWhereCount = is_null($query->wheres)
            ? 0 : count($query->wheres);

        $result = $scope(...array_values($parameters)) ?? $this;

        if (count((array)$query->wheres) > $originalWhereCount) {
            $this->addNewWheresWithinGroup($query, $originalWhereCount);
        }

        return $result;
    }

    /**
     * Apply the given named scope on the current builder instance.
     *
     * @param string $scope
     * @param array $parameters
     * @return mixed
     */
    protected function callNamedScope(string $scope, array $parameters = []): mixed
    {
        return $this->callScope(function (...$parameters) use ($scope) {
            return $this->model->callNamedScope($scope, $parameters);
        }, $parameters);
    }

    /**
     * Nest where conditions by slicing them at the given where count.
     *
     * @param QueryBuilder $query
     * @param int $originalWhereCount
     * @return void
     */
    protected function addNewWheresWithinGroup(QueryBuilder $query, int $originalWhereCount): void
    {
        // Here, we totally remove all of the where clauses since we are going to
        // rebuild them as nested queries by slicing the groups of wheres into
        // their own sections. This is to prevent any confusing logic order.
        $allWheres = $query->wheres;

        $query->wheres = [];

        $this->groupWhereSliceForScope(
            $query, array_slice($allWheres, 0, $originalWhereCount)
        );

        $this->groupWhereSliceForScope(
            $query, array_slice($allWheres, $originalWhereCount)
        );
    }

    /**
     * Slice where conditions at the given offset and add them to the query as a nested condition.
     *
     * @param QueryBuilder $query
     * @param array $whereSlice
     * @return void
     */
    protected function groupWhereSliceForScope(QueryBuilder $query, array $whereSlice): void
    {
        $whereBooleans = collect($whereSlice)->pluck('boolean');

        // Here we'll check if the given subset of where clauses contains any "or"
        // booleans and in this case create a nested where expression. That way
        // we don't add any unnecessary nesting thus keeping the query clean.
        if ($whereBooleans->contains('or')) {
            $query->wheres[] = $this->createNestedWhere(
                $whereSlice, $whereBooleans->first()
            );
        } else {
            $query->wheres = array_merge($query->wheres, $whereSlice);
        }
    }

    /**
     * Create a where array with nested where conditions.
     *
     * @param array $whereSlice
     * @param string $boolean
     * @return array
     */
    protected function createNestedWhere(array $whereSlice, string $boolean = 'and'): array
    {
        $whereGroup = $this->getQuery()->forNestedWhere();

        $whereGroup->wheres = $whereSlice;

        return ['type' => 'Nested', 'query' => $whereGroup, 'boolean' => $boolean];
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param mixed $relations
     * @return $this
     */
    public function with(mixed $relations): self
    {
        $eagerLoad = $this->parseWithRelations(is_string($relations) ? func_get_args() : $relations);

        $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

        return $this;
    }

    /**
     * Prevent the specified relations from being eager loaded.
     *
     * @param mixed $relations
     * @return $this
     */
    public function without(mixed $relations): self
    {
        $this->eagerLoad = array_diff_key($this->eagerLoad, array_flip(
            is_string($relations) ? func_get_args() : $relations
        ));

        return $this;
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param array $attributes
     * @return Model|static
     */
    public function newModelInstance(array $attributes = []): Model|static
    {
        return $this->model->newInstance($attributes)->setConnection(
            $this->query->getConnection()->getName()
        );
    }

    /**
     * Parse a list of relations into individuals.
     *
     * @param array $relations
     * @return array
     */
    protected function parseWithRelations(array $relations): array
    {
        $results = [];

        foreach ($relations as $name => $constraints) {
            // If the "name" value is a numeric key, we can assume that no constraints
            // have been specified. We will just put an empty Closure there so that
            // we can treat these all the same while we are looping through them.
            if (is_numeric($name)) {
                $name = $constraints;

                [$name, $constraints] = Str::contains($name, ':')
                    ? $this->createSelectWithConstraint($name)
                    : [$name, static function () {
                        //
                    }];
            }

            // We need to separate out any nested includes, which allows the developers
            // to load deep relationships using "dots" without stating each level of
            // the relationship with its own key in the array of eager-load names.
            $results = $this->addNestedWiths($name, $results);

            $results[$name] = $constraints;
        }

        return $results;
    }

    /**
     * Create a constraint to select the given columns for the relation.
     *
     * @param string $name
     * @return array
     */
    protected function createSelectWithConstraint(string $name): array
    {
        return [explode(':', $name)[0], static function ($query) use ($name) {
            $query->select(explode(',', explode(':', $name)[1]));
        }];
    }

    /**
     * Parse the nested relationships in a relation.
     *
     * @param string $name
     * @param array $results
     * @return array
     */
    protected function addNestedWiths(string $name, array $results): array
    {
        $progress = [];

        // If the relation has already been set on the result array, we will not set it
        // again, since that would override any constraints that were already placed
        // on the relationships. We will only set the ones that are not specified.
        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;

            if (!isset($results[$last = implode('.', $progress)])) {
                $results[$last] = static function () {
                    //
                };
            }
        }

        return $results;
    }

    /**
     * Apply query-time casts to the model instance.
     *
     * @param array $casts
     * @return $this
     */
    public function withCasts(array $casts): self
    {
        $this->model->mergeCasts($casts);

        return $this;
    }

    /**
     * Get the underlying query builder instance.
     *
     * @return QueryBuilder
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Set the underlying query builder instance.
     *
     * @param QueryBuilder $query
     * @return $this
     */
    public function setQuery(QueryBuilder $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get a base query builder instance.
     *
     * @return QueryBuilder
     */
    public function toBase(): QueryBuilder
    {
        return $this->applyScopes()->getQuery();
    }

    /**
     * Get the relationships being eagerly loaded.
     *
     * @return array
     */
    public function getEagerLoads(): array
    {
        return $this->eagerLoad;
    }

    /**
     * Set the relationships being eagerly loaded.
     *
     * @param array $eagerLoad
     * @return $this
     */
    public function setEagerLoads(array $eagerLoad): self
    {
        $this->eagerLoad = $eagerLoad;

        return $this;
    }

    /**
     * Get the default key name of the table.
     *
     * @return string
     */
    protected function defaultKeyName(): string
    {
        return $this->getModel()->getKeyName();
    }

    /**
     * Get the model instance being queried.
     *
     * @return Model|static
     */
    public function getModel(): Model|static
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        $this->query->from($model->getTable());

        return $this;
    }

    /**
     * Qualify the given column name by the model's table.
     *
     * @param string $column
     * @return string
     */
    public function qualifyColumn(string $column): string
    {
        return $this->model->qualifyColumn($column);
    }

    /**
     * Get the given macro by name.
     *
     * @param string $name
     * @return Closure
     */
    public function getMacro(string $name): callable
    {
        return Arr::get($this->localMacros, $name);
    }

    /**
     * Checks if a macro is registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasMacro(string $name): bool
    {
        return isset($this->localMacros[$name]);
    }

    /**
     * Get the given global macro by name.
     *
     * @param string $name
     * @return Closure
     */
    public static function getGlobalMacro(string $name): callable
    {
        return Arr::get(static::$macros, $name);
    }

    /**
     * Checks if a global macro is registered.
     *
     * @param string $name
     * @return bool
     */
    public static function hasGlobalMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically access builder proxies.
     *
     * @param string $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get(string $key)
    {
        if ($key === 'orWhere') {
            return new HigherOrderBuilderProxy($this, $key);
        }

        throw new RuntimeException("Property [{$key}] does not exist on the Eloquent builder instance.");
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if ($method === 'macro') {
            $this->localMacros[$parameters[0]] = $parameters[1];

            return null;
        }

        if ($this->hasMacro($method)) {
            array_unshift($parameters, $this);

            return $this->localMacros[$method](...$parameters);
        }

        if (static::hasGlobalMacro($method)) {
            if (static::$macros[$method] instanceof Closure) {
                return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
            }

            return call_user_func_array(static::$macros[$method], $parameters);
        }

        if ($this->hasNamedScope($method)) {
            return $this->callNamedScope($method, $parameters);
        }

        if (in_array($method, $this->passthru, true)) {
            return $this->toBase()->{$method}(...$parameters);
        }

        $this->forwardCallTo($this->query, $method, $parameters);

        return $this;
    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if ($method === 'macro') {
            static::$macros[$parameters[0]] = $parameters[1];

            return null;
        }

        if ($method === 'mixin') {
            static::registerMixin($parameters[0], $parameters[1] ?? true);
            return null;
        }

        if (!static::hasGlobalMacro($method)) {
            static::throwBadMethodCallException($method);
        }

        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(Closure::bind(static::$macros[$method], null, static::class), $parameters);
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }

    /**
     * Register the given mixin with the builder.
     *
     * @param string $mixin
     * @param bool $replace
     * @return void
     */
    protected static function registerMixin(string $mixin, bool $replace): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || !static::hasGlobalMacro($method->name)) {
                $method->setAccessible(true);

                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Force a clone of the underlying query builder when cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
