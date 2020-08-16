<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema;

use Closure;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Mini\Database\Mysql\Connection;
use LogicException;
use Mini\Database\Mysql\Schema\Grammars\Grammar;
use RuntimeException;

class Builder
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The schema grammar instance.
     *
     * @var Grammar
     */
    protected Grammars\Grammar $grammar;

    /**
     * The Blueprint resolver callback.
     *
     * @var Closure
     */
    protected Closure $resolver;

    /**
     * The default string length for migrations.
     *
     * @var int
     */
    public static int $defaultStringLength = 255;

    /**
     * Create a new database Schema manager.
     *
     * @param Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * Set the default string length for migrations.
     *
     * @param int $length
     * @return void
     */
    public static function defaultStringLength(int $length): void
    {
        static::$defaultStringLength = $length;
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     * @return bool
     */
    public function hasTable(string $table): bool
    {
        $table = $this->connection->getTablePrefix() . $table;

        return count($this->connection->selectFromWriteConnection(
                $this->grammar->compileTableExists(), [$table]
            )) > 0;
    }

    /**
     * Determine if the given table has a given column.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public function hasColumn(string $table, string $column): bool
    {
        return in_array(strtolower($column), array_map('strtolower', $this->getColumnListing($table)), true);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param string $table
     * @param array $columns
     * @return bool
     */
    public function hasColumns(string $table, array $columns): bool
    {
        $tableColumns = array_map('strtolower', $this->getColumnListing($table));

        foreach ($columns as $column) {
            if (!in_array(strtolower($column), $tableColumns, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the data type for the given column name.
     *
     * @param string $table
     * @param string $column
     * @return string
     */
    public function getColumnType(string $table, string $column): string
    {
        $table = $this->connection->getTablePrefix() . $table;

        return $this->connection->getDoctrineColumn($table, $column)->getType()->getName();
    }

    /**
     * Get the column listing for a given table.
     *
     * @param string $table
     * @return array
     */
    public function getColumnListing(string $table): array
    {
        $results = $this->connection->selectFromWriteConnection($this->grammar->compileColumnListing(
            $this->connection->getTablePrefix() . $table
        ));

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }

    /**
     * Modify a table on the schema.
     *
     * @param string $table
     * @param Closure $callback
     * @return void
     */
    public function table(string $table, Closure $callback): void
    {
        $this->build($this->createBlueprint($table, $callback));
    }

    /**
     * Create a new table on the schema.
     *
     * @param string $table
     * @param Closure $callback
     * @return void
     */
    public function create(string $table, Closure $callback): void
    {
        $this->build(tap($this->createBlueprint($table), static function ($blueprint) use ($callback) {
            $blueprint->create();

            $callback($blueprint);
        }));
    }

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     * @return void
     */
    public function drop(string $table): void
    {
        $this->build(tap($this->createBlueprint($table), static function ($blueprint) {
            $blueprint->drop();
        }));
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param string $table
     * @return void
     */
    public function dropIfExists($table): void
    {
        $this->build(tap($this->createBlueprint($table), static function ($blueprint) {
            $blueprint->dropIfExists();
        }));
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function dropAllTables(): void
    {
        throw new LogicException('This database driver does not support dropping all tables.');
    }

    /**
     * Drop all views from the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function dropAllViews(): void
    {
        throw new LogicException('This database driver does not support dropping all views.');
    }

    /**
     * Drop all types from the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function dropAllTypes(): void
    {
        throw new LogicException('This database driver does not support dropping all types.');
    }

    /**
     * Get all of the table names for the database.
     *
     * @return void|mixed
     *
     * @throws LogicException
     */
    public function getAllTables()
    {
        throw new LogicException('This database driver does not support getting all tables.');
    }

    /**
     * Rename a table on the schema.
     *
     * @param string $from
     * @param string $to
     * @return void
     */
    public function rename(string $from, string $to): void
    {
        $this->build(tap($this->createBlueprint($from), static function ($blueprint) use ($to) {
            $blueprint->rename($to);
        }));
    }

    /**
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enableForeignKeyConstraints(): bool
    {
        return $this->connection->statement(
            $this->grammar->compileEnableForeignKeyConstraints()
        );
    }

    /**
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disableForeignKeyConstraints(): bool
    {
        return $this->connection->statement(
            $this->grammar->compileDisableForeignKeyConstraints()
        );
    }

    /**
     * Execute the blueprint to build / modify the table.
     *
     * @param Blueprint $blueprint
     * @return void
     */
    protected function build(Blueprint $blueprint): void
    {
        $blueprint->build($this->connection, $this->grammar);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string $table
     * @param Closure|null $callback
     * @return Blueprint
     */
    protected function createBlueprint(string $table, ?Closure $callback = null): Blueprint
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $table, $callback, $prefix);
        }

        return new Blueprint($table, $callback, $prefix);
    }

    /**
     * Register a custom Doctrine mapping type.
     *
     * @param string $class
     * @param string $name
     * @param string $type
     * @return void
     *
     * @throws DBALException
     * @throws RuntimeException
     */
    public function registerCustomDoctrineType(string $class, string $name, string $type): void
    {
        if (!$this->connection->isDoctrineAvailable()) {
            throw new RuntimeException(
                'Registering a custom Doctrine type requires Doctrine DBAL (doctrine/dbal).'
            );
        }

        if (!Type::hasType($name)) {
            Type::addType($name, $class);

            $this->connection
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping($type, $name);
        }
    }

    /**
     * Get the database connection instance.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set the database connection instance.
     *
     * @param Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param Closure $resolver
     * @return void
     */
    public function blueprintResolver(Closure $resolver): void
    {
        $this->resolver = $resolver;
    }
}
