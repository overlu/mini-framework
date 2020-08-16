<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Concerns;

use Closure;

trait HidesAttributes
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected array $hidden = [];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected array $visible = [];

    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    public function getHidden(): array
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
     *
     * @param array $hidden
     * @return $this
     */
    public function setHidden(array $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array
     */
    public function getVisible(): array
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     *
     * @param array $visible
     * @return $this
     */
    public function setVisible(array $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible.
     *
     * @param array|string|null $attributes
     * @return $this
     */
    public function makeVisible($attributes): self
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->hidden = array_diff($this->hidden, $attributes);

        if (!empty($this->visible)) {
            $this->visible = array_merge($this->visible, $attributes);
        }

        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible if the given truth test passes.
     *
     * @param bool|Closure $condition
     * @param array|string|null $attributes
     * @return $this
     */
    public function makeVisibleIf($condition, $attributes): self
    {
        $condition = $condition instanceof Closure ? $condition($this) : $condition;

        return $condition ? $this->makeVisible($attributes) : $this;
    }

    /**
     * Make the given, typically visible, attributes hidden.
     *
     * @param array|string|null $attributes
     * @return $this
     */
    public function makeHidden($attributes): self
    {
        $this->hidden = array_merge(
            $this->hidden, is_array($attributes) ? $attributes : func_get_args()
        );

        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden if the given truth test passes.
     *
     * @param bool|Closure $condition
     * @param array|string|null $attributes
     * @return $this
     */
    public function makeHiddenIf($condition, $attributes): self
    {
        $condition = $condition instanceof Closure ? $condition($this) : $condition;

        return value($condition) ? $this->makeHidden($attributes) : $this;
    }
}
