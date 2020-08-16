<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema;

class MySqlBuilder extends Builder
{
    /**
     * Determine if the given table exists.
     *
     * @param string $table
     * @return bool
     */
    public function hasTable(string $table): bool
    {
        $table = $this->connection->getTablePrefix() . $table;

        return count($this->connection->select(
                $this->grammar->compileTableExists(), [$this->connection->getDatabaseName(), $table]
            )) > 0;
    }

    /**
     * Get the column listing for a given table.
     *
     * @param string $table
     * @return array
     */
    public function getColumnListing(string $table): array
    {
        $table = $this->connection->getTablePrefix() . $table;

        $results = $this->connection->select(
            $this->grammar->compileColumnListing(), [$this->connection->getDatabaseName(), $table]
        );

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables(): void
    {
        $tables = [];

        foreach ($this->getAllTables() as $row) {
            $row = (array)$row;

            $tables[] = reset($row);
        }

        if (empty($tables)) {
            return;
        }

        $this->disableForeignKeyConstraints();

        $this->connection->statement(
            $this->grammar->compileDropAllTables($tables)
        );

        $this->enableForeignKeyConstraints();
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     */
    public function dropAllViews(): void
    {
        $views = [];

        foreach ($this->getAllViews() as $row) {
            $row = (array)$row;

            $views[] = reset($row);
        }

        if (empty($views)) {
            return;
        }

        $this->connection->statement(
            $this->grammar->compileDropAllViews($views)
        );
    }

    /**
     * Get all of the table names for the database.
     *
     * @return array
     */
    public function getAllTables(): array
    {
        return $this->connection->select(
            $this->grammar->compileGetAllTables()
        );
    }

    /**
     * Get all of the view names for the database.
     *
     * @return array
     */
    public function getAllViews(): array
    {
        return $this->connection->select(
            $this->grammar->compileGetAllViews()
        );
    }
}
