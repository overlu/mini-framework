<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Closure;
use Generator;
use Mini\Database\Mysql\Query\Builder;
use Mini\Database\Mysql\Query\Expression;
use Throwable;

interface ConnectionInterface
{
    /**
     * Begin a fluent query against a database table.
     *
     * @param Closure|Builder|string $table
     * @param string|null $as
     * @return Builder
     */
    public function table($table, ?string $as = null): Builder;

    /**
     * Get a new raw query expression.
     *
     * @param mixed $value
     * @return Expression
     */
    public function raw($value): Expression;

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return mixed
     */
    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true);

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return array
     */
    public function select(string $query, array $bindings = [], bool $useReadPdo = true): array;

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return Generator
     */
    public function cursor(string $query, array $bindings = [], bool $useReadPdo = true): Generator;

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function insert(string $query, array $bindings = []): bool;

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update(string $query, array $bindings = []): int;

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function delete(string $query, array $bindings = []): int;

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement(string $query, array $bindings = []): bool;

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingStatement(string $query, array $bindings = []): int;

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param string $query
     * @return bool
     */
    public function unprepared(string $query): bool;

    /**
     * Prepare the query bindings for execution.
     *
     * @param array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings): array;

    /**
     * Execute a Closure within a transaction.
     *
     * @param Closure $callback
     * @param int $attempts
     * @return mixed
     *
     * @throws Throwable
     */
    public function transaction(Closure $callback, int $attempts = 1);

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollBack(): void;

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel(): int;

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param Closure $callback
     * @return array
     */
    public function pretend(Closure $callback): array;
}
