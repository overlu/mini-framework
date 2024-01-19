<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Database\Mysql\Schema\Builder;

/**
 * Class Schema
 * @method static Builder create(string $table, \Closure $callback)
 * @method static Builder disableForeignKeyConstraints()
 * @method static Builder drop(string $table)
 * @method static Builder dropIfExists(string $table)
 * @method static Builder enableForeignKeyConstraints()
 * @method static Builder rename(string $from, string $to)
 * @method static Builder table(string $table, \Closure $callback)
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
    public static function connection($name)
    {
        return app('db')->connection($name)->getSchemaBuilder();
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return Builder
     */
    protected static function getFacadeAccessor(): Builder
    {
        return app('db')->connection()->getSchemaBuilder();
    }
}