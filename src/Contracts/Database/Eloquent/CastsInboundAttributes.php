<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Database\Eloquent;

use Mini\Database\Mysql\Eloquent\Model;

interface CastsInboundAttributes
{
    /**
     * Transform the attribute to its underlying model values.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return array
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array;
}
