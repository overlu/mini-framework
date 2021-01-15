<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Relations\Concerns;

use Closure;
use Mini\Database\Mysql\Eloquent\Model;

trait SupportsDefaultModels
{
    /**
     * Indicates if a default model instance should be used.
     *
     * Alternatively, may be a Closure or array.
     *
     * @var Closure|array|bool
     */
    protected $withDefault;

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
    abstract protected function newRelatedInstanceFor(?Model $parent): Model;

    /**
     * Return a new model instance in case the relationship does not exist.
     *
<<<<<<< HEAD
     * @param \Closure|array|bool $callback
=======
     * @param Closure|array|bool $callback
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return $this
     */
    public function withDefault($callback = true): self
    {
        $this->withDefault = $callback;

        return $this;
    }

    /**
     * Get the default value for this relation.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Eloquent\Model $parent
     * @return \Mini\Database\Mysql\Eloquent\Model|null
=======
     * @param Model $parent
     * @return Model|null
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function getDefaultFor(Model $parent): ?Model
    {
        if (!$this->withDefault) {
<<<<<<< HEAD
            return;
=======
            return null;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
        }

        $instance = $this->newRelatedInstanceFor($parent);

        if (is_callable($this->withDefault)) {
            return call_user_func($this->withDefault, $instance, $parent) ?: $instance;
        }

        if (is_array($this->withDefault)) {
            $instance->forceFill($this->withDefault);
        }

        return $instance;
    }
}
