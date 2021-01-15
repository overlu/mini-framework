<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Collection;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\Relations\Concerns\SupportsDefaultModels;

class MorphOne extends MorphOneOrMany
{
    use SupportsDefaultModels;

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        if (is_null($this->getParentKey())) {
            return $this->getDefaultFor($this->parent);
        }

        return $this->query->first() ?: $this->getDefaultFor($this->parent);
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
        return $this->matchOne($models, $results, $relation);
    }

    /**
     * Make a new related instance for the given model.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
     * @return \Mini\Database\Mysql\Eloquent\Model
=======
     * @param Model $parent
     * @return Model
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function newRelatedInstanceFor(Model $parent): Model
    {
        return $this->related->newInstance()
            ->setAttribute($this->getForeignKeyName(), $parent->{$this->localKey})
            ->setAttribute($this->getMorphType(), $this->morphClass);
    }
}
