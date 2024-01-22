<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

/**
 * @method \Doctrine\DBAL\Driver\PDOConnection getPdo()
 * @method \Mini\Database\Mysql\ConnectionInterface connection(string $name = null)
 * @method \Mini\Database\Mysql\Query\Builder table(string $table)
 * @method \Mini\Database\Mysql\Query\Expression raw($value)
 * @method array getQueryLog()
 * @method array prepareBindings(array $bindings)
 * @method array pretend(\Closure $callback)
 * @method array select(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method bool insert(string $query, array $bindings = [])
 * @method bool logging()
 * @method bool statement(string $query, array $bindings = [])
 * @method bool unprepared(string $query)
 * @method int affectingStatement(string $query, array $bindings = [])
 * @method int delete(string $query, array $bindings = [])
 * @method int transactionLevel()
 * @method int update(string $query, array $bindings = [])
 * @method mixed selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
 * @method mixed transaction(\Closure $callback, int $attempts = 1)
 * @method string getDefaultConnection()
 * @method void beginTransaction()
 * @method void commit()
 * @method void enableQueryLog()
 * @method void disableQueryLog()
 * @method void flushQueryLog()
 * @method void listen(\Closure $callback)
 * @method void rollBack()
 * @method void setDefaultConnection(string $name)
 *
 * @see \Mini\Database\Mysql\DatabaseManager
 * @see \Mini\Database\Mysql\Connection
 */
interface DB
{
}
