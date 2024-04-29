<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Database\Mysql\PDO\MySqlDriver;
use Mini\Database\Mysql\Query\Grammars\MySqlGrammar as QueryGrammar;
use Mini\Database\Mysql\Query\Processors\MySqlProcessor;
use Mini\Database\Mysql\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Mini\Database\Mysql\Schema\MySqlBuilder;

class MySqlConnection extends Connection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Mini\Database\Mysql\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Mini\Database\Mysql\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Mini\Database\Mysql\Schema\Grammars\MySqlGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Mini\Database\Mysql\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }

    /**
     * @return MySqlDriver
     */
    protected function getDoctrineDriver(): MySqlDriver
    {
        return new MySqlDriver();
    }
}
