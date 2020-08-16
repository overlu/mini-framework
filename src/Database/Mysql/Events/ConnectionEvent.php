<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Events;

use Mini\Database\Mysql\Connection;

abstract class ConnectionEvent
{
    /**
     * The name of the connection.
     *
     * @var string
     */
    public ?string $connectionName;

    /**
     * The database connection instance.
     *
     * @var Connection
     */
    public Connection $connection;

    /**
     * Create a new event instance.
     *
     * @param Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
