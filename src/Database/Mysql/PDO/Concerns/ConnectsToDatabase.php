<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\PDO\Concerns;

use Mini\Database\Mysql\PDO\Connection;
use InvalidArgumentException;
use PDO;

trait ConnectsToDatabase
{
    /**
     * Create a new database connection.
     *
     * @param array $params
     * @param string|null $username
     * @param string|null $password
     * @param array $driverOptions
     * @return Connection
     *
     * @throws InvalidArgumentException
     */
    public function connect(array $params, string $username = null, string $password = null, array $driverOptions = []): Connection
    {
        if (!isset($params['pdo']) || !$params['pdo'] instanceof PDO) {
            throw new InvalidArgumentException('Mini requires the "pdo" property to be set and be a PDO instance.');
        }

        return new Connection($params['pdo']);
    }
}
