<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Connectors;

use Exception;
use InvalidArgumentException;
use PDO;
use Throwable;

class SQLiteConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \PDO
=======
     * @return PDO
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws Throwable
     */
    public function connect(array $config): PDO
    {
        $options = $this->getOptions($config);

        // SQLite supports "in-memory" databases that only last as long as the owning
        // connection does. These are useful for tests or for short lifetime store
        // querying. In-memory databases may only have a single open connection.
        if ($config['database'] === ':memory:') {
            return $this->createConnection('sqlite::memory:', $config, $options);
        }

        $path = realpath($config['database']);

        // Here we'll verify that the SQLite database exists before going any further
        // as the developer probably wants to know if the database exists and this
        // SQLite driver will not throw any exception if it does not by default.
        if ($path === false) {
            throw new InvalidArgumentException("Database ({$config['database']}) does not exist.");
        }

        return $this->createConnection("sqlite:{$path}", $config, $options);
    }
}
