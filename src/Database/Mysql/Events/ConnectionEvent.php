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
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Connection $connection
=======
     * @param Connection $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
