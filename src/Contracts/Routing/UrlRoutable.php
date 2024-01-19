<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Routing;

use Mini\Database\Mysql\Eloquent\Model;

interface UrlRoutable
{
    /**
     * Get the value of the model's route key.
     *
     * @return mixed
     */
    public function getRouteKey(): mixed;

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string;

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param string|null $field
     * @return Model|null
     */
    public function resolveRouteBinding(mixed $value, string $field = null): ?Model;

    /**
     * Retrieve the child model for a bound value.
     *
     * @param string $childType
     * @param mixed $value
     * @param string|null $field
     * @return Model|null
     */
    public function resolveChildRouteBinding(string $childType, mixed $value, ?string $field): ?Model;
}
