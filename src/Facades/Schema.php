<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Database\Mysql\Capsule\Manager;

/**
 * Class Schema
 * @method static \Mini\Database\Mysql\Schema\Builder create(string $table, \Closure $callback)
 * @method static \Mini\Database\Mysql\Schema\Builder disableForeignKeyConstraints()
 * @method static \Mini\Database\Mysql\Schema\Builder drop(string $table)
 * @method static \Mini\Database\Mysql\Schema\Builder dropIfExists(string $table)
 * @method static \Mini\Database\Mysql\Schema\Builder enableForeignKeyConstraints()
 * @method static \Mini\Database\Mysql\Schema\Builder rename(string $from, string $to)
 * @method static \Mini\Database\Mysql\Schema\Builder table(string $table, \Closure $callback)
 * @method static bool hasColumn(string $table, string $column)
 * @method static bool hasColumns(string $table, array $columns)
 * @method static bool dropColumns(string $table, array $columns)
 * @method static bool hasTable(string $table)
 * @method static void defaultStringLength(int $length)
 * @method static void registerCustomDoctrineType(string $class, string $name, string $type)
 * @package Mini\Facades
 */
class Schema extends Facade
{
    /**
     * @return \Mini\Database\Mysql\Schema\Builder|void
     */
    protected static function getFacadeAccessor()
    {
        return Manager::schema();
    }
}