<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\PDO;

use Doctrine\DBAL\Driver\PDO\Result;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOStatement;

class Connection implements ServerInfoAwareConnection
{
    /**
     * The underlying PDO connection.
     *
     * @var PDO
     */
    protected PDO $connection;

    /**
     * Create a new PDO connection instance.
     *
     * @param PDO $connection
     * @return void
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute an SQL statement.
     *
     * @param string $statement
     * @return int
     */
    public function exec(string $statement): int
    {
        $result = $this->connection->exec($statement);

        \assert($result !== false);

        return $result;
    }

    /**
     * Prepare a new SQL statement.
     *
     * @param string $sql
     * @return StatementInterface
     */
    public function prepare(string $sql): StatementInterface
    {
        return $this->createStatement(
            $this->connection->prepare($sql)
        );
    }

    /**
     * Execute a new query against the connection.
     *
     * @param string $sql
     * @return ResultInterface
     */
    public function query(string $sql): ResultInterface
    {
        $stmt = $this->connection->query($sql);

        \assert($stmt instanceof PDOStatement);

        return new Result($stmt);
    }

    /**
     * Get the last insert ID.
     *
     * @param string|null $name
     * @return int|string
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return $this->connection->lastInsertId();
        }

        return $this->connection->lastInsertId($name);
    }

    /**
     * Create a new statement instance.
     *
     * @param PDOStatement $stmt
     * @return Statement
     */
    protected function createStatement(PDOStatement $stmt): Statement
    {
        return new Statement($stmt);
    }

    /**
     * Begin a new database transaction.
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit a database transaction.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback a database transaction.
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Wrap quotes around the given input.
     *
     * @param string $input
     * @param string $type
     * @return string
     */
    public function quote($input, $type = ParameterType::STRING)
    {
        return $this->connection->quote($input, $type);
    }

    /**
     * Get the server version for the connection.
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get the wrapped PDO connection.
     *
     * @return PDO
     */
    public function getWrappedConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * @return object|resource
     */
    public function getNativeConnection()
    {
        return $this->connection;
    }
}
