<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Events;

use Mini\Database\Mysql\Connection;

class QueryExecuted
{
    /**
     * The SQL query that was executed.
     *
     * @var string
     */
    public string $sql;

    /**
     * The array of query bindings.
     *
     * @var array
     */
    public array $bindings;

    /**
     * The number of milliseconds it took to execute the query.
     *
     * @var float
     */
    public ?float $time;

    /**
     * The database connection instance.
     *
     * @var Connection
     */
    public Connection $connection;

    /**
     * The database connection name.
     *
     * @var string
     */
    public ?string $connectionName;

    /**
     * Create a new event instance.
     *
     * @param string $sql
     * @param array $bindings
     * @param float|null $time
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Connection $connection
=======
     * @param Connection $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public function __construct(string $sql, array $bindings, ?float $time, Connection $connection)
    {
        $this->sql = $sql;
        $this->time = $time;
        $this->bindings = $bindings;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
