<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Eloquent\Concerns;

use Closure;
use Mini\Database\Mysql\Eloquent\Scope;
use Mini\Support\Arr;
use InvalidArgumentException;

trait HasGlobalScopes
{
    /**
     * Register a new global scope on the model.
     *
     * @param Scope|Closure|string $scope
     * @param Closure|null $implementation
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public static function addGlobalScope($scope, Closure $implementation = null)
    {
        if (is_string($scope) && !is_null($implementation)) {
            return static::$globalScopes[static::class][$scope] = $implementation;
        }

        if ($scope instanceof Closure) {
            return static::$globalScopes[static::class][spl_object_hash($scope)] = $scope;
        }

        if ($scope instanceof Scope) {
            return static::$globalScopes[static::class][get_class($scope)] = $scope;
        }

        throw new InvalidArgumentException('Global scope must be an instance of Closure or Scope.');
    }

    /**
     * Determine if a model has a global scope.
     *
     * @param Scope|string $scope
     * @return bool
     */
    public static function hasGlobalScope($scope): bool
    {
        return !is_null(static::getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the model.
     *
     * @param Scope|string $scope
     * @return Scope|Closure|null
     */
    public static function getGlobalScope($scope)
    {
        if (is_string($scope)) {
            return Arr::get(static::$globalScopes, static::class . '.' . $scope);
        }

        return Arr::get(
            static::$globalScopes, static::class . '.' . get_class($scope)
        );
    }

    /**
     * Get the global scopes for this class instance.
     *
     * @return array
     */
    public function getGlobalScopes(): array
    {
        return Arr::get(static::$globalScopes, static::class, []);
    }
}
