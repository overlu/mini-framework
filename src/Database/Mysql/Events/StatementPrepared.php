<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Events;

use Mini\Database\Mysql\Connection;
use PDOStatement;

class StatementPrepared
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    public Connection $connection;

    /**
     * The PDO statement.
     *
     * @var PDOStatement
     */
    public PDOStatement $statement;

    /**
     * Create a new event instance.
     *
     * @param Connection $connection
     * @param PDOStatement $statement
     * @return void
     */
    public function __construct(Connection $connection, PDOStatement $statement)
    {
        $this->statement = $statement;
        $this->connection = $connection;
    }
}
