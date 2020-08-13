<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
namespace Mini\Database\Mysql;

use Mini\Database\Mysql\Capsule\Manager;

class Model
{
    protected $table;

    protected $model;

    public function __construct()
    {
        $this->model = Manager::table($this->table);
    }

    public function __call($name, $arguments)
    {
        return $this->model->{$name}(...$arguments);
    }
}