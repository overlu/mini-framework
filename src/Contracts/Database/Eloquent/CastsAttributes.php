<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Database\Eloquent;

use Mini\Database\Mysql\Eloquent\Model;

interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return mixed
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed;

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return array|string
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array|string;
}
