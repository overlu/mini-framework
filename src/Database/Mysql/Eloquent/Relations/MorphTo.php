<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use BadMethodCallException;
use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Collection;
use Mini\Database\Mysql\Eloquent\Model;

class MorphTo extends BelongsTo
{
    /**
     * The type of the polymorphic relation.
     *
     * @var string
     */
    protected string $morphType = '';

    /**
     * The models whose relations are being eager loaded.
     *
     * @var Collection
     */
    protected Collection $models;

    /**
     * All of the models keyed by ID.
     *
     * @var array
     */
    protected array $dictionary = [];

    /**
     * A buffer of dynamic calls to query macros.
     *
     * @var array
     */
    protected array $macroBuffer = [];

    /**
     * A map of relations to load for each individual morph type.
     *
     * @var array
     */
    protected array $morphableEagerLoads = [];

    /**
     * A map of relationship counts to load for each individual morph type.
     *
     * @var array
     */
    protected array $morphableEagerLoadCounts = [];

    /**
     * Create a new morph to relationship instance.
     *
     * @param Builder $query
     * @param Model $parent
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $type
     * @param string $relation
     * @return void
     */
    public function __construct(Builder $query, Model $parent, string $foreignKey, string $ownerKey, string $type, string $relation)
    {
        $this->morphType = $type;

        parent::__construct($query, $parent, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models): void
    {
        $this->buildDictionary($this->models = Collection::make($models));
    }

    /**
     * Build a dictionary with the models.
     *
     * @param Collection $models
     * @return void
     */
    protected function buildDictionary(Collection $models): void
    {
        foreach ($models as $model) {
            if ($model->{$this->morphType}) {
                $this->dictionary[$model->{$this->morphType}][$model->{$this->foreignKey}][] = $model;
            }
        }
    }

    /**
     * Get the results of the relationship.
     *
     * Called via eager load method of Eloquent query builder.
     *
     * @return mixed
     */
    public function getEager(): Collection
    {
        foreach (array_keys($this->dictionary) as $type) {
            $this->matchToMorphParents($type, $this->getResultsByType($type));
        }

        return $this->models;
    }

    /**
     * Get all of the relation results for a type.
     *
     * @param string $type
     * @return Collection
     */
    protected function getResultsByType(string $type): Collection
    {
        $instance = $this->createModelByType($type);

        $ownerKey = $this->ownerKey ?? $instance->getKeyName();

        $query = $this->replayMacros($instance->newQuery())
            ->mergeConstraintsFrom($this->getQuery())
            ->with(array_merge(
                $this->getQuery()->getEagerLoads(),
                (array)($this->morphableEagerLoads[get_class($instance)] ?? [])
            ))
            ->withCount(
                (array)($this->morphableEagerLoadCounts[get_class($instance)] ?? [])
            );

        $whereIn = $this->whereInMethod($instance, $ownerKey);

        return $query->{$whereIn}(
            $instance->getTable() . '.' . $ownerKey, $this->gatherKeysByType($type)
        )->get();
    }

    /**
     * Gather all of the foreign keys for a given type.
     *
     * @param string $type
     * @return array
     */
    protected function gatherKeysByType(string $type): array
    {
        return array_keys($this->dictionary[$type]);
    }

    /**
     * Create a new model instance by type.
     *
     * @param string $type
     * @return Model
     */
    public function createModelByType(string $type): Model
    {
        $class = Model::getActualClassNameForMorph($type);

        return tap(new $class, function ($instance) {
            if (!$instance->getConnectionName()) {
                $instance->setConnection($this->getConnection()->getName());
            }
        });
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
        return $models;
    }

    /**
     * Match the results for a given type to their parents.
     *
     * @param string $type
     * @param Collection $results
     * @return void
     */
    protected function matchToMorphParents(string $type, Collection $results): void
    {
        foreach ($results as $result) {
            $ownerKey = !is_null($this->ownerKey) ? $result->{$this->ownerKey} : $result->getKey();

            if (isset($this->dictionary[$type][$ownerKey])) {
                foreach ($this->dictionary[$type][$ownerKey] as $model) {
                    $model->setRelation($this->relationName, $result);
                }
            }
        }
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param int|Model|string $model
     * @return Model
     */
    public function associate(int|Model|string $model): Model
    {
        $this->parent->setAttribute(
            $this->foreignKey, $model instanceof Model ? $model->getKey() : null
        );

        $this->parent->setAttribute(
            $this->morphType, $model instanceof Model ? $model->getMorphClass() : null
        );

        return $this->parent->setRelation($this->relationName, $model);
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return Model
     */
    public function dissociate(): Model
    {
        $this->parent->setAttribute($this->foreignKey, null);

        $this->parent->setAttribute($this->morphType, null);

        return $this->parent->setRelation($this->relationName, null);
    }

    /**
     * Touch all of the related models for the relationship.
     *
     * @return void
     */
    public function touch(): void
    {
        if (!is_null($this->child->{$this->foreignKey})) {
            parent::touch();
        }
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param Model $parent
     * @return Model
     */
    protected function newRelatedInstanceFor(Model $parent): Model
    {
        return $parent->{$this->getRelationName()}()->getRelated()->newInstance();
    }

    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getMorphType(): string
    {
        return $this->morphType;
    }

    /**
     * Get the dictionary used by the relationship.
     *
     * @return array
     */
    public function getDictionary(): array
    {
        return $this->dictionary;
    }

    /**
     * Specify which relations to load for a given morph type.
     *
     * @param array $with
     * @return MorphTo
     */
    public function morphWith(array $with): self
    {
        $this->morphableEagerLoads = array_merge(
            $this->morphableEagerLoads, $with
        );

        return $this;
    }

    /**
     * Specify which relationship counts to load for a given morph type.
     *
     * @param array $withCount
     * @return MorphTo
     */
    public function morphWithCount(array $withCount): self
    {
        $this->morphableEagerLoadCounts = array_merge(
            $this->morphableEagerLoadCounts, $withCount
        );

        return $this;
    }

    /**
     * Replay stored macro calls on the actual related instance.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function replayMacros(Builder $query): Builder
    {
        foreach ($this->macroBuffer as $macro) {
            $query->{$macro['method']}(...$macro['parameters']);
        }

        return $query;
    }

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            $result = parent::__call($method, $parameters);

            if (in_array($method, ['select', 'selectRaw', 'selectSub', 'addSelect', 'withoutGlobalScopes'])) {
                $this->macroBuffer[] = compact('method', 'parameters');
            }

            return $result;
        }

            // If we tried to call a method that does not exist on the parent Builder instance,
            // we'll assume that we want to call a query macro (e.g. withTrashed) that only
            // exists on related models. We will just store the call and replay it later.
        catch (BadMethodCallException $e) {
            $this->macroBuffer[] = compact('method', 'parameters');

            return $this;
        }
    }
}
