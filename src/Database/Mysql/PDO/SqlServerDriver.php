<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\PDO;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;

class SqlServerDriver extends AbstractSQLServerDriver
{
    /**
     * Create a new database connection.
     *
     * @param array $params
     * @param string|null $username
     * @param string|null $password
     * @param array $driverOptions
     * @return SqlServerConnection
     */
    public function connect(array $params, string $username = null, string $password = null, array $driverOptions = []): SqlServerConnection
    {
        return new SqlServerConnection(
            new Connection($params['pdo'])
        );
    }

    public function getName(): string
    {
        return 'pdo_sqlsrv';
    }
}
