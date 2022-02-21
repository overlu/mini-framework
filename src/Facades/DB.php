<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class DB
 * @method static \Doctrine\DBAL\Driver\PDO\Connection getPdo()
 * @method static \Mini\Database\Mysql\ConnectionInterface connection(string $name = null)
 * @method static \Mini\Database\Mysql\Query\Builder table(string $table)
 * @method static \Mini\Database\Mysql\Query\Expression raw($value)
 * @method static array getQueryLog()
 * @method static array prepareBindings(array $bindings)
 * @method static array pretend(\Closure $callback)
 * @method static array select(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static bool insert(string $query, array $bindings = [])
 * @method static bool logging()
 * @method static bool statement(string $query, array $bindings = [])
 * @method static bool unprepared(string $query)
 * @method static int affectingStatement(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static int transactionLevel()
 * @method static int update(string $query, array $bindings = [])
 * @method static mixed selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method static mixed transaction(\Closure $callback, int $attempts = 1)
 * @method static string getDefaultConnection()
 * @method static void beginTransaction()
 * @method static void commit()
 * @method static void enableQueryLog()
 * @method static void disableQueryLog()
 * @method static void flushQueryLog()
 * @method static void listen(\Closure $callback)
 * @method static void rollBack()
 * @method static void setDefaultConnection(string $name)
 *
 * @see \Mini\Database\Mysql\DatabaseManager
 * @see \Mini\Database\Mysql\Connection
 * @package Mini\Facades
 */
class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'db';
    }
}