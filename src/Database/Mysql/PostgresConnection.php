<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Doctrine\DBAL\Driver\PDOPgSql\Driver as DoctrineDriver;
use Mini\Database\Mysql\Query\Grammars\PostgresGrammar as QueryGrammar;
use Mini\Database\Mysql\Query\Processors\PostgresProcessor;
use Mini\Database\Mysql\Schema\Grammars\PostgresGrammar as SchemaGrammar;
use Mini\Database\Mysql\Schema\PostgresBuilder;
use PDO;
use PDOStatement;

class PostgresConnection extends Connection
{
    /**
     * Bind values to their parameters in the given statement.
     *
     * @param PDOStatement $statement
     * @param array $bindings
     * @return void
     */
    public function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $pdoParam = PDO::PARAM_INT;
            } elseif (is_resource($value)) {
                $pdoParam = PDO::PARAM_LOB;
            } else {
                $pdoParam = PDO::PARAM_STR;
            }

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $pdoParam
            );
        }
    }

    /**
     * Get the default query grammar instance.
     *
     * @return QueryGrammar|mixed
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return PostgresBuilder|mixed
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new PostgresBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return SchemaGrammar|mixed
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return PostgresProcessor|mixed
     */
    protected function getDefaultPostProcessor()
    {
        return new PostgresProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return DoctrineDriver
     */
    protected function getDoctrineDriver(): DoctrineDriver
    {
        return new DoctrineDriver;
    }
}
