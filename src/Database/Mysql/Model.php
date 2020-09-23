<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */

namespace Mini\Database\Mysql;

use Mini\Database\Mysql\Capsule\Manager;
use Mini\Database\Mysql\Query\Builder;

/**
 * Class Model
 * @package Mini\Database\Mysql
 * @mixin Builder
 */
class Model
{
    protected string $table;

    protected Builder $model;

    public function __construct()
    {
        $this->model = Manager::table($this->table);
    }

    public function __call($name, $arguments)
    {
        return $this->model->{$name}(...$arguments);
    }
}