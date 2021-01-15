<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Concerns;

use Closure;
use Mini\Database\Mysql\Eloquent\Builder;
use Mini\Database\Mysql\Eloquent\Collection;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\Relations\BelongsTo;
use Mini\Database\Mysql\Eloquent\Relations\BelongsToMany;
use Mini\Database\Mysql\Eloquent\Relations\HasMany;
use Mini\Database\Mysql\Eloquent\Relations\HasManyThrough;
use Mini\Database\Mysql\Eloquent\Relations\HasOne;
use Mini\Database\Mysql\Eloquent\Relations\HasOneThrough;
use Mini\Database\Mysql\Eloquent\Relations\MorphMany;
use Mini\Database\Mysql\Eloquent\Relations\MorphOne;
use Mini\Database\Mysql\Eloquent\Relations\MorphTo;
use Mini\Database\Mysql\Eloquent\Relations\MorphToMany;
use Mini\Database\Mysql\Eloquent\Relations\Relation;
use Mini\Support\Arr;
use Mini\Support\Str;

trait HasRelationships
{
    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected array $relations = [];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected array $touches = [];

    /**
     * The many to many relationship methods.
     *
     * @var array
     */
    public static array $manyMethods = [
        'belongsToMany', 'morphToMany', 'morphedByMany',
    ];

    /**
     * The relation resolver callbacks.
     *
     * @var array
     */
    protected static array $relationResolvers = [];

    /**
     * Define a dynamic relation resolver.
     *
     * @param string $name
<<<<<<< HEAD
     * @param \Closure $callback
=======
     * @param Closure $callback
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public static function resolveRelationUsing(string $name, Closure $callback): void
    {
        static::$relationResolvers = array_replace_recursive(
            static::$relationResolvers,
            [static::class => [$name => $callback]]
        );
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasOne
=======
     * @return HasOne
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    /**
     * Instantiate a new HasOne relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasOne
=======
     * @param Builder $query
     * @param Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return HasOne
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newHasOne(Builder $query, Model $parent, string $foreignKey, string $localKey): HasOne
    {
        return new HasOne($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Define a has-one-through relationship.
     *
     * @param string $related
     * @param string $through
     * @param string|null $firstKey
     * @param string|null $secondKey
     * @param string|null $localKey
     * @param string|null $secondLocalKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasOneThrough
=======
     * @return HasOneThrough
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function hasOneThrough(string $related, string $through, ?string $firstKey = null, ?string $secondKey = null, ?string $localKey = null, ?string $secondLocalKey = null): HasOneThrough
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        return $this->newHasOneThrough(
            $this->newRelatedInstance($related)->newQuery(), $this, $through,
            $firstKey, $secondKey, $localKey ?: $this->getKeyName(),
            $secondLocalKey ?: $through->getKeyName()
        );
    }

    /**
     * Instantiate a new HasOneThrough relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $farParent
     * @param \Mini\Database\Mysql\Eloquent\Model $throughParent
=======
     * @param Builder $query
     * @param Model $farParent
     * @param Model $throughParent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $firstKey
     * @param string $secondKey
     * @param string $localKey
     * @param string $secondLocalKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasOneThrough
=======
     * @return HasOneThrough
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, string $firstKey, string $secondKey, string $localKey, string $secondLocalKey): HasOneThrough
    {
        return new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphOne
=======
     * @return MorphOne
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function morphOne(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null): MorphOne
    {
        $instance = $this->newRelatedInstance($related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphOne($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $localKey);
    }

    /**
     * Instantiate a new MorphOne relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphOne
=======
     * @param Builder $query
     * @param Model $parent
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return MorphOne
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newMorphOne(Builder $query, Model $parent, string $type, string $id, string $localKey): MorphOne
    {
        return new MorphOne($query, $parent, $type, $id, $localKey);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $ownerKey
     * @param string|null $relation
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\BelongsTo
=======
     * @return BelongsTo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null, ?string $relation = null): BelongsTo
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation) . '_' . $instance->getKeyName();
        }

        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return $this->newBelongsTo(
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );
    }

    /**
     * Instantiate a new BelongsTo relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $child
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relation
     * @return \Mini\Database\Mysql\Eloquent\Relations\BelongsTo
=======
     * @param Builder $query
     * @param Model $child
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relation
     * @return BelongsTo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newBelongsTo(Builder $query, Model $child, string $foreignKey, string $ownerKey, string $relation): BelongsTo
    {
        return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param string|null $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $ownerKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphTo
=======
     * @return MorphTo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function morphTo(?string $name = null, ?string $type = null, ?string $id = null, ?string $ownerKey = null): MorphTo
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        $name = $name ?: $this->guessBelongsToRelation();

        [$type, $id] = $this->getMorphs(
            Str::snake($name), $type, $id
        );

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. In this case we'll just pass in a dummy query where we
        // need to remove any eager loads that may already be defined on a model.
        return empty($class = $this->{$type})
            ? $this->morphEagerTo($name, $type, $id, $ownerKey)
            : $this->morphInstanceTo($class, $name, $type, $id, $ownerKey);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $ownerKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphTo
=======
     * @return MorphTo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function morphEagerTo(string $name, string $type, string $id, string $ownerKey): MorphTo
    {
        return $this->newMorphTo(
            $this->newQuery()->setEagerLoads([]), $this, $id, $ownerKey, $type, $name
        );
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param string $target
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $ownerKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphTo
=======
     * @return MorphTo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function morphInstanceTo(string $target, string $name, string $type, string $id, string $ownerKey): MorphTo
    {
        $instance = $this->newRelatedInstance(
            static::getActualClassNameForMorph($target)
        );

        return $this->newMorphTo(
            $instance->newQuery(), $this, $id, $ownerKey ?? $instance->getKeyName(), $type, $name
        );
    }

    /**
     * Instantiate a new MorphTo relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
=======
     * @param Builder $query
     * @param Model $parent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $type
     * @param string $relation
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphTo
=======
     * @return MorphTo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newMorphTo(Builder $query, Model $parent, string $foreignKey, string $ownerKey, string $type, string $relation): MorphTo
    {
        return new MorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }

    /**
     * Retrieve the actual class name for a given morph class.
     *
     * @param string $class
<<<<<<< HEAD
     * @return string
=======
     * @return string|mixed
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public static function getActualClassNameForMorph(string $class)
    {
        return Arr::get(Relation::morphMap() ?: [], $class, $class);
    }

    /**
     * Guess the "belongs to" relationship name.
     *
     * @return string
     */
    protected function guessBelongsToRelation(): string
    {
        [$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasMany
=======
     * @return HasMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey
        );
    }

    /**
     * Instantiate a new HasMany relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasMany
=======
     * @param Builder $query
     * @param Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @return HasMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newHasMany(Builder $query, Model $parent, string $foreignKey, string $localKey): HasMany
    {
        return new HasMany($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Define a has-many-through relationship.
     *
     * @param string $related
     * @param string $through
     * @param string|null $firstKey
     * @param string|null $secondKey
     * @param string|null $localKey
     * @param string|null $secondLocalKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasManyThrough
=======
     * @return HasManyThrough
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function hasManyThrough(string $related, string $through, ?string $firstKey = null, ?string $secondKey = null, ?string $localKey = null, ?string $secondLocalKey = null): HasManyThrough
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        return $this->newHasManyThrough(
            $this->newRelatedInstance($related)->newQuery(),
            $this,
            $through,
            $firstKey,
            $secondKey,
            $localKey ?: $this->getKeyName(),
            $secondLocalKey ?: $through->getKeyName()
        );
    }

    /**
     * Instantiate a new HasManyThrough relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $farParent
     * @param \Mini\Database\Mysql\Eloquent\Model $throughParent
=======
     * @param Builder $query
     * @param Model $farParent
     * @param Model $throughParent
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $firstKey
     * @param string $secondKey
     * @param string $localKey
     * @param string $secondLocalKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\HasManyThrough
=======
     * @return HasManyThrough
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, string $firstKey, string $secondKey, string $localKey, string $secondLocalKey): HasManyThrough
    {
        return new HasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphMany
=======
     * @return MorphMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null): MorphMany
    {
        $instance = $this->newRelatedInstance($related);

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return $this->newMorphMany($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $localKey);
    }

    /**
     * Instantiate a new MorphMany relationship.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Builder $query
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphMany
=======
     * @param Builder $query
     * @param Model $parent
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return MorphMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newMorphMany(Builder $query, Model $parent, string $type, string $id, string $localKey): MorphMany
    {
        return new MorphMany($query, $parent, $type, $id, $localKey);
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param string $related
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param string|null $relation
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\BelongsToMany
=======
     * @return BelongsToMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null,
                                  ?string $parentKey = null, ?string $relatedKey = null, ?string $relation = null): BelongsToMany
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related, $instance);
        }

        return $this->newBelongsToMany(
            $instance->newQuery(), $this, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $relation
        );
    }

    /**
     * Instantiate a new BelongsToMany relationship.
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
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\BelongsToMany
=======
     * @return BelongsToMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function newBelongsToMany(Builder $query, Model $parent, string $table, string $foreignPivotKey, string $relatedPivotKey,
                                        string $parentKey, string $relatedKey, ?string $relationName = null): BelongsToMany
    {
        return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param bool $inverse
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphToMany
=======
     * @return MorphToMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function morphToMany(string $related, string $name, ?string $table = null, ?string $foreignPivotKey = null,
                                ?string $relatedPivotKey = null, ?string $parentKey = null,
                                ?string $relatedKey = null, bool $inverse = false): MorphToMany
    {
        $caller = $this->guessBelongsToManyRelation();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name . '_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        if (!$table) {
            $words = preg_split('/(_)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $lastWord = array_pop($words);

            $table = implode('', $words) . Str::plural($lastWord);
        }

        return $this->newMorphToMany(
            $instance->newQuery(), $this, $name, $table,
            $foreignPivotKey, $relatedPivotKey, $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(), $caller, $inverse
        );
    }

    /**
     * Instantiate a new MorphToMany relationship.
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
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphToMany
     */
    protected function newMorphToMany(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
                                      $relatedPivotKey, $parentKey, $relatedKey,
                                      $relationName = null, $inverse = false)
=======
     * @return MorphToMany
     */
    protected function newMorphToMany(Builder $query, Model $parent, string $name, string $table, string $foreignPivotKey,
                                      string $relatedPivotKey, string $parentKey, string $relatedKey,
                                      ?string $relationName = null, bool $inverse = false): MorphToMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
    {
        return new MorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
            $relationName, $inverse);
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignPivotKey
     * @param string|null $relatedPivotKey
     * @param string|null $parentKey
     * @param string|null $relatedKey
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Eloquent\Relations\MorphToMany
=======
     * @return MorphToMany
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function morphedByMany(string $related, string $name, ?string $table = null, ?string $foreignPivotKey = null,
                                  ?string $relatedPivotKey = null, ?string $parentKey = null, ?string $relatedKey = null): MorphToMany
    {
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        // For the inverse of the polymorphic many-to-many relations, we will change
        // the way we determine the foreign and other keys, as it is the opposite
        // of the morph-to-many method since we're figuring out these inverses.
        $relatedPivotKey = $relatedPivotKey ?: $name . '_id';

        return $this->morphToMany(
            $related, $name, $table, $foreignPivotKey,
            $relatedPivotKey, $parentKey, $relatedKey, true
        );
    }

    /**
     * Get the relationship name of the belongsToMany relationship.
     *
     * @return string|null
     */
    protected function guessBelongsToManyRelation(): ?string
    {
<<<<<<< HEAD
        $caller = Arr::first(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function ($trace) {
            return !in_array(
                $trace['function'],
                array_merge(static::$manyMethods, ['guessBelongsToManyRelation'])
            );
=======
        $caller = Arr::first(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), static function ($trace) {
            return !in_array($trace['function'], array_merge(static::$manyMethods, ['guessBelongsToManyRelation']), true);
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
        });

        return !is_null($caller) ? $caller['function'] : null;
    }

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param string $related
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model|null $instance
=======
     * @param Model|null $instance
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function joiningTable(string $related, ?Model $instance = null): string
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        $segments = [
            $instance ? $instance->joiningTableSegment()
                : Str::snake(class_basename($related)),
            $this->joiningTableSegment(),
        ];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($segments);

        return strtolower(implode('_', $segments));
    }

    /**
     * Get this model's half of the intermediate table name for belongsToMany relationships.
     *
     * @return string
     */
    public function joiningTableSegment(): string
    {
        return Str::snake(class_basename($this));
    }

    /**
     * Determine if the model touches a given relation.
     *
     * @param string $relation
     * @return bool
     */
    public function touches(string $relation): bool
    {
        return in_array($relation, $this->touches, true);
    }

    /**
     * Touch the owning relations of the model.
     *
     * @return void
     */
    public function touchOwners(): void
    {
        foreach ($this->touches as $relation) {
            $this->$relation()->touch();

            if ($this->$relation instanceof self) {
                $this->$relation->fireModelEvent('saved', false);

                $this->$relation->touchOwners();
            } elseif ($this->$relation instanceof Collection) {
                $this->$relation->each->touchOwners();
            }
        }
    }

    /**
     * Get the polymorphic relationship columns.
     *
     * @param string $name
     * @param string $type
     * @param string $id
     * @return array
     */
    protected function getMorphs(string $name, string $type, string $id): array
    {
        return [$type ?: $name . '_type', $id ?: $name . '_id'];
    }

    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function getMorphClass(): string
    {
        $morphMap = Relation::morphMap();

<<<<<<< HEAD
        if (!empty($morphMap) && in_array(static::class, $morphMap)) {
=======
        if (!empty($morphMap) && in_array(static::class, $morphMap, true)) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            return array_search(static::class, $morphMap, true);
        }

        return static::class;
    }

    /**
     * Create a new model instance for a related model.
     *
     * @param string $class
     * @return mixed
     */
    protected function newRelatedInstance(string $class)
    {
        return tap(new $class, function ($instance) {
            if (!$instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
        });
    }

    /**
     * Get all the loaded relations for the instance.
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
     *
     * @param string $relation
     * @return mixed
     */
    public function getRelation(string $relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
     *
     * @param string $key
     * @return bool
     */
    public function relationLoaded(string $key): bool
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the given relationship on the model.
     *
     * @param string $relation
     * @param mixed $value
     * @return $this
     */
    public function setRelation(string $relation, $value): self
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Unset a loaded relationship.
     *
     * @param string $relation
     * @return $this
     */
    public function unsetRelation(string $relation): self
    {
        unset($this->relations[$relation]);

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * @param array $relations
     * @return $this
     */
    public function setRelations(array $relations): self
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * Duplicate the instance and unset all the loaded relations.
     *
     * @return $this
     */
    public function withoutRelations(): self
    {
        $model = clone $this;

        return $model->unsetRelations();
    }

    /**
     * Unset all the loaded relations for the instance.
     *
     * @return $this
     */
    public function unsetRelations(): self
    {
        $this->relations = [];

        return $this;
    }

    /**
     * Get the relationships that are touched on save.
     *
     * @return array
     */
    public function getTouchedRelations(): array
    {
        return $this->touches;
    }

    /**
     * Set the relationships that are touched on save.
     *
     * @param array $touches
     * @return $this
     */
    public function setTouchedRelations(array $touches): self
    {
        $this->touches = $touches;

        return $this;
    }
}
