<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Collection;

class HasMany extends HasOneOrMany
{
    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return !is_null($this->getParentKey())
            ? $this->query->get()
            : $this->related->newCollection();
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param array $models
     * @param string $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
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
     * @param \Mini\Database\Mysql\Eloquent\Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchMany($models, $results, $relation);
    }
}
