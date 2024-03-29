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
    protected Closure|array|bool $withDefault = [];

    /**
     * Make a new related instance for the given model.
     *
     * @param Model $parent
     * @return Model
     */
    abstract protected function newRelatedInstanceFor(Model $parent): Model;

    /**
     * Return a new model instance in case the relationship does not exist.
     *
     * @param bool|array|Closure $callback
     * @return $this
     */
    public function withDefault(bool|array|Closure $callback = true): self
    {
        $this->withDefault = $callback;

        return $this;
    }

    /**
     * Get the default value for this relation.
     *
     * @param Model $parent
     * @return Model|null
     */
    protected function getDefaultFor(Model $parent): ?Model
    {
        if (!$this->withDefault) {
            return null;
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
