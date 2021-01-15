<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Support\Arr;
use Mini\Support\Collection;

class MorphToMany extends BelongsToMany
{
    /**
     * The type of the polymorphic relation.
     *
     * @var string
     */
    protected string $morphType;

    /**
     * The class name of the morph type constraint.
     *
     * @var string
     */
    protected string $morphClass;

    /**
     * Indicates if we are connecting the inverse of the relation.
     *
     * This primarily affects the morphClass constraint.
     *
     * @var bool
     */
    protected bool $inverse;

    /**
     * Create a new morph to many relationship instance.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
=======
     * @param Builder $query
     * @param Model $parent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $name
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param string|null $relationName
     * @param bool $inverse
     * @return void
     */
    public function __construct(Builder $query, Model $parent, string $name, string $table, string $foreignPivotKey,
                                string $relatedPivotKey, string $parentKey, string $relatedKey, ?string $relationName = null, bool $inverse = false)
    {
        $this->inverse = $inverse;
        $this->morphType = $name . '_type';
        $this->morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();

        parent::__construct(
            $query, $parent, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, $relationName
        );
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints(): self
    {
        parent::addWhereConstraints();

        $this->query->where($this->table . '.' . $this->morphType, $this->morphClass);

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
        parent::addEagerConstraints($models);

        $this->query->where($this->table . '.' . $this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot attachment record.
     *
     * @param int $id
     * @param bool $timed
     * @return array
     */
    protected function baseAttachRecord(int $id, bool $timed): array
    {
        return Arr::add(
            parent::baseAttachRecord($id, $timed), $this->morphType, $this->morphClass
        );
    }

    /**
     * Add the constraints for a relationship count query.
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
            $this->table . '.' . $this->morphType, $this->morphClass
        );
    }

    /**
     * Get the pivot models that are currently attached.
     *
     * @return Collection
     */
    protected function getCurrentlyAttachedPivots(): Collection
    {
        return parent::getCurrentlyAttachedPivots()->map(function ($record) {
            return $record instanceof MorphPivot
                ? $record->setMorphType($this->morphType)
                    ->setMorphClass($this->morphClass)
                : $record;
        });
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Mini\Database\Mysql\Query\Builder
     */
    public function newPivotQuery(): \Mini\Database\Mysql\Query\Builder
    {
        return parent::newPivotQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * Create a new pivot model instance.
     *
     * @param array $attributes
     * @param bool $exists
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\Pivot
=======
     * @return Pivot
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function newPivot(array $attributes = [], $exists = false): Pivot
    {
        $using = $this->using;

        $pivot = $using ? $using::fromRawAttributes($this->parent, $attributes, $this->table, $exists)
            : MorphPivot::fromAttributes($this->parent, $attributes, $this->table, $exists);

        $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey)
            ->setMorphType($this->morphType)
            ->setMorphClass($this->morphClass);

        return $pivot;
    }

    /**
     * Get the pivot columns for the relation.
     *
     * "pivot_" is prefixed at each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns(): array
    {
        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey, $this->morphType];

        return collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $this->table . '.' . $column . ' as pivot_' . $column;
        })->unique()->all();
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
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass(): string
    {
        return $this->morphClass;
    }

    /**
     * Get the indicator for a reverse relationship.
     *
     * @return bool
     */
    public function getInverse(): bool
    {
        return $this->inverse;
    }
}
