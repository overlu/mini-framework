<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations;

use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Support\Str;

class MorphPivot extends Pivot
{
    /**
     * The type of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected string $morphType;

    /**
     * The value of the polymorphic relation.
     *
     * Explicitly define this so it's not included in saved attributes.
     *
     * @var string
     */
    protected string $morphClass;

    /**
     * Set the keys for a save update query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @return \Mini\Database\Mysql\Eloquent\Builder
=======
     * @param Builder $query
     * @return Builder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function setKeysForSaveQuery(Builder $query): Builder
    {
        $query->where($this->morphType, $this->morphClass);

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Delete the pivot model record from the database.
     *
     * @return int|mixed
     * @throws \Exception
     */
    public function delete()
    {
        if (isset($this->attributes[$this->getKeyName()])) {
            return (int)parent::delete();
        }

        if ($this->fireModelEvent('deleting') === false) {
            return 0;
        }

        $query = $this->getDeleteQuery();

        $query->where($this->morphType, $this->morphClass);

        return tap($query->delete(), function () {
            $this->fireModelEvent('deleted', false);
        });
    }

    /**
     * Set the morph type for the pivot.
     *
     * @param string $morphType
     * @return $this
     */
    public function setMorphType(string $morphType): self
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * Set the morph class for the pivot.
     *
     * @param string $morphClass
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphPivot
=======
     * @return MorphPivot
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function setMorphClass(string $morphClass): self
    {
        $this->morphClass = $morphClass;

        return $this;
    }

    /**
     * Get the queueable identity for the entity.
     *
     * @return mixed
     */
    public function getQueueableId()
    {
        if (isset($this->attributes[$this->getKeyName()])) {
            return $this->getKey();
        }

        return sprintf(
            '%s:%s:%s:%s:%s:%s',
            $this->foreignKey, $this->getAttribute($this->foreignKey),
            $this->relatedKey, $this->getAttribute($this->relatedKey),
            $this->morphType, $this->morphClass
        );
    }

    /**
     * Get a new query to restore one or more models by their queueable IDs.
     *
     * @param array|int $ids
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Builder
=======
     * @return Builder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function newQueryForRestoration($ids): Builder
    {
        if (is_array($ids)) {
            return $this->newQueryForCollectionRestoration($ids);
        }

        if (!Str::contains($ids, ':')) {
            return parent::newQueryForRestoration($ids);
        }

        $segments = explode(':', $ids);

        return $this->newQueryWithoutScopes()
            ->where($segments[0], $segments[1])
            ->where($segments[2], $segments[3])
            ->where($segments[4], $segments[5]);
    }

    /**
     * Get a new query to restore multiple models by their queueable IDs.
     *
     * @param array $ids
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Builder
=======
     * @return Builder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newQueryForCollectionRestoration(array $ids): Builder
    {
        if (!Str::contains($ids[0], ':')) {
            return parent::newQueryForRestoration($ids);
        }

        $query = $this->newQueryWithoutScopes();

        foreach ($ids as $id) {
            $segments = explode(':', $id);

            $query->orWhere(static function ($query) use ($segments) {
                return $query->where($segments[0], $segments[1])
                    ->where($segments[2], $segments[3])
                    ->where($segments[4], $segments[5]);
            });
        }

        return $query;
    }
}
