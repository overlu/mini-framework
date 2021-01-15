<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Closure;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as DoctrineDriver;
use Mini\Database\Mysql\Query\Grammars\SQLiteGrammar as QueryGrammar;
use Mini\Database\Mysql\Query\Processors\SQLiteProcessor;
use Mini\Database\Mysql\Schema\Grammars\SQLiteGrammar as SchemaGrammar;
use Mini\Database\Mysql\Schema\SQLiteBuilder;
use PDO;

class SQLiteConnection extends Connection
{
    /**
     * Create a new database connection instance.
     *
<<<<<<< HEAD
     * @param \PDO|\Closure $pdo
=======
     * @param PDO|Closure $pdo
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $database
     * @param string $tablePrefix
     * @param array $config
     * @return void
     */
    public function __construct($pdo, string $database = '', string $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $enableForeignKeyConstraints = $this->getForeignKeyConstraintsConfigurationValue();

        if ($enableForeignKeyConstraints === null) {
            return;
        }

        $enableForeignKeyConstraints
            ? $this->getSchemaBuilder()->enableForeignKeyConstraints()
            : $this->getSchemaBuilder()->disableForeignKeyConstraints();
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
     * @return SQLiteBuilder|mixed
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SQLiteBuilder($this);
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
     * @return SQLiteProcessor|mixed
     */
    protected function getDefaultPostProcessor()
    {
        return new SQLiteProcessor;
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

    /**
     * Get the database connection foreign key constraints configuration option.
     *
     * @return bool|null
     */
    protected function getForeignKeyConstraintsConfigurationValue(): ?bool
    {
        return $this->getConfig('foreign_key_constraints');
    }
}
