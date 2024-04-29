<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\PDO;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\PDO\SQLSrv\Statement;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;

class SqlServerConnection implements \Doctrine\DBAL\Driver\Connection
{
    /**
     * The underlying connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Create a new SQL Server connection instance.
     *
     * @param Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Prepare a new SQL statement.
     *
     * @param string $sql
     * @return StatementInterface
     */
    public function prepare(string $sql): StatementInterface
    {
        return new Statement(
            $this->connection->prepare($sql)
        );
    }

    /**
     * Execute a new query against the connection.
     *
     * @param string $sql
     * @return Result
     */
    public function query(string $sql): Result
    {
        return $this->connection->query($sql);
    }

    /**
     * Execute an SQL statement.
     *
     * @param string $statement
     * @return int
     */
    public function exec(string $statement): int
    {
        return $this->connection->exec($statement);
    }

    /**
     * Get the last insert ID.
     *
     * @param null $name
     * @return int|string
     * @throws Exception
     */
    public function lastInsertId($name = null): int|string
    {
        if ($name === null) {
            return $this->connection->lastInsertId($name);
        }

        return $this->prepare('SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?')
            ->execute([$name])
            ->fetchOne();
    }

    /**
     * Begin a new database transaction.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback a database transaction.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Wrap quotes around the given input.
     *
     * @param string $value
     * @param int $type
     * @return string
     */
    public function quote(string $value, $type = ParameterType::STRING): string
    {
        $val = $this->connection->quote($value, $type);

        // Fix for a driver version terminating all values with null byte...
        if (str_contains($val, "\0")) {
            $val = \substr($val, 0, -1);
        }

        return $val;
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->connection->getServerVersion();
    }

    /**
     * Get the wrapped PDO connection.
     *
     * @return PDO
     */
    public function getWrappedConnection(): PDO
    {
        return $this->connection->getWrappedConnection();
    }

    /**
     * @return object|resource
     */
    public function getNativeConnection()
    {
        return $this->connection->getNativeConnection();
    }
}
