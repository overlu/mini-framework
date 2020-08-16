<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema;

class SQLiteBuilder extends Builder
{
    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables(): void
    {
        if ($this->connection->getDatabaseName() !== ':memory:') {
            return $this->refreshDatabaseFile();
        }

        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllTables());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());

        $this->connection->select($this->grammar->compileRebuild());
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews(): void
    {
        $this->connection->select($this->grammar->compileEnableWriteableSchema());

        $this->connection->select($this->grammar->compileDropAllViews());

        $this->connection->select($this->grammar->compileDisableWriteableSchema());

        $this->connection->select($this->grammar->compileRebuild());
    }

    /**
     * Empty the database file.
     *
     * @return void
     */
    public function refreshDatabaseFile(): void
    {
        file_put_contents($this->connection->getDatabaseName(), '');
    }
}
