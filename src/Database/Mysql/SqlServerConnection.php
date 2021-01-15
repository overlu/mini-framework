<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Closure;
use Doctrine\DBAL\Driver\PDOSqlsrv\Driver as DoctrineDriver;
use Mini\Database\Mysql\Query\Grammars\SqlServerGrammar as QueryGrammar;
use Mini\Database\Mysql\Query\Processors\SqlServerProcessor;
use Mini\Database\Mysql\Schema\Grammars\SqlServerGrammar as SchemaGrammar;
use Mini\Database\Mysql\Schema\SqlServerBuilder;
use Throwable;

class SqlServerConnection extends Connection
{
    /**
     * Execute a Closure within a transaction.
     *
<<<<<<< HEAD
     * @param \Closure $callback
=======
     * @param Closure $callback
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param int $attempts
     * @return mixed
     *
     * @throws Throwable
     */
    public function transaction(Closure $callback, int $attempts = 1)
    {
        for ($a = 1; $a <= $attempts; $a++) {
            if ($this->getDriverName() === 'sqlsrv') {
                return parent::transaction($callback);
            }

            $this->getPdo()->exec('BEGIN TRAN');

            // We'll simply execute the given callback within a try / catch block
            // and if we catch any exception we can rollback the transaction
            // so that none of the changes are persisted to the database.
            try {
                $result = $callback($this);

                $this->getPdo()->exec('COMMIT TRAN');
            }

                // If we catch an exception, we will roll back so nothing gets messed
                // up in the database. Then we'll re-throw the exception so it can
                // be handled how the developer sees fit for their applications.
            catch (Throwable $e) {
                $this->getPdo()->exec('ROLLBACK TRAN');

                throw $e;
            }

            return $result;
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
     * @return SqlServerBuilder|mixed
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SqlServerBuilder($this);
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
     * @return SqlServerProcessor|mixed
     */
    protected function getDefaultPostProcessor()
    {
        return new SqlServerProcessor;
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
