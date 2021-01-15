<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Doctrine\DBAL\Driver\PDOMySql\Driver as DoctrineDriver;
use Mini\Database\Mysql\Query\Grammars\MySqlGrammar as QueryGrammar;
use Mini\Database\Mysql\Query\Processors\MySqlProcessor;
use Mini\Database\Mysql\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Mini\Database\Mysql\Schema\MySqlBuilder;

class MySqlConnection extends Connection
{
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
     * @return MySqlBuilder
     */
    public function getSchemaBuilder(): MySqlBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlBuilder($this);
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
     * @return MySqlProcessor|mixed
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
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
