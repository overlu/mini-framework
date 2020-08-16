<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Support\Str;
use PDOException;
use Throwable;

class QueryException extends PDOException
{
    /**
     * The SQL for the query.
     *
     * @var string
     */
    protected string $sql;

    /**
     * The bindings for the query.
     *
     * @var array
     */
    protected array $bindings;

    /**
     * Create a new query exception instance.
     *
     * @param string $sql
     * @param array $bindings
     * @param Throwable $previous
     * @return void
     */
    public function __construct(string $sql, array $bindings, Throwable $previous)
    {
        parent::__construct('', 0, $previous);

        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($sql, $bindings, $previous);

        if ($previous instanceof PDOException) {
            $this->errorInfo = $previous->errorInfo;
        }
    }

    /**
     * Format the SQL error message.
     *
     * @param string $sql
     * @param array $bindings
     * @param Throwable $previous
     * @return string
     */
    protected function formatMessage(string $sql, array $bindings, Throwable $previous): string
    {
        return $previous->getMessage() . ' (SQL: ' . Str::replaceArray('?', $bindings, $sql) . ')';
    }

    /**
     * Get the SQL for the query.
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * Get the bindings for the query.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
