<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Database\Mysql\Capsule\Manager;
use Mini\Database\Mysql\Query\Builder;

/**
 * Class Model
 * @package Mini\Database\Mysql
<<<<<<< HEAD
 * @mixin Builder
=======
 * @mixin Query\Builder
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
 */
class Model
{
    protected string $table;

<<<<<<< HEAD
    protected Builder $model;
=======
    protected Query\Builder $model;
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be

    public function __construct()
    {
        $this->model = Manager::table($this->table);
    }

    public function __call($name, $arguments)
    {
        return $this->model->{$name}(...$arguments);
    }
}