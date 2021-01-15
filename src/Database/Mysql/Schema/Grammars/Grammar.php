<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema\Grammars;

use Doctrine\DBAL\Schema\AbstractSchemaManager as SchemaManager;
use Doctrine\DBAL\Schema\TableDiff;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Grammar as BaseGrammar;
use Mini\Database\Mysql\Query\Expression;
use Mini\Database\Mysql\Schema\Blueprint;
use Mini\Support\Fluent;
use RuntimeException;

abstract class Grammar extends BaseGrammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected bool $transactions = false;

    /**
     * The commands to be executed outside of create or alter command.
     *
     * @var array
     */
    protected array $fluentCommands = [];

    /**
     * Compile a rename column command.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $command
     * @param \Mini\Database\Mysql\Connection $connection
=======
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param Connection $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    public function compileRenameColumn(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        return RenameColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Compile a change column command into a series of SQL statements.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $command
     * @param \Mini\Database\Mysql\Connection $connection
=======
     * @param Blueprint $blueprint
     * @param Fluent $command
     * @param Connection $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     *
     * @throws RuntimeException
     */
    public function compileChange(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        return ChangeColumn::compile($this, $blueprint, $command, $connection);
    }

    /**
     * Compile a foreign key command.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $command
=======
     * @param Blueprint $blueprint
     * @param Fluent $command
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function compileForeign(Blueprint $blueprint, Fluent $command): string
    {
        // We need to prepare several of the elements of the foreign key definition
        // before we can create the SQL, such as wrapping the tables and convert
        // an array of columns to comma-delimited strings for the SQL queries.
        $sql = sprintf('alter table %s add constraint %s ',
            $this->wrapTable($blueprint),
            $this->wrap($command->index)
        );

        // Once we have the initial portion of the SQL statement we will add on the
        // key name, table name, and referenced columns. These will complete the
        // main portion of the SQL statement and this SQL will almost be done.
        $sql .= sprintf('foreign key (%s) references %s (%s)',
            $this->columnize($command->columns),
            $this->wrapTable($command->on),
            $this->columnize((array)$command->references)
        );

        // Once we have the basic foreign key creation statement constructed we can
        // build out the syntax for what should happen on an update or delete of
        // the affected columns, which will get something like "cascade", etc.
        if (!is_null($command->onDelete)) {
            $sql .= " on delete {$command->onDelete}";
        }

        if (!is_null($command->onUpdate)) {
            $sql .= " on update {$command->onUpdate}";
        }

        return $sql;
    }

    /**
     * Compile the blueprint's column definitions.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
=======
     * @param Blueprint $blueprint
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    protected function getColumns(Blueprint $blueprint): array
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column) {
            // Each of the column types have their own compiler functions which are tasked
            // with turning the column definition into its SQL format for this platform
            // used by the connection. The column's modifiers are compiled and added.
            $sql = $this->wrap($column) . ' ' . $this->getType($column);

            $columns[] = $this->addModifiers($sql, $blueprint, $column);
        }

        return $columns;
    }

    /**
     * Get the SQL for the column data type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function getType(Fluent $column): string
    {
        return $this->{'type' . ucfirst($column->type)}($column);
    }

    /**
     * Create the column definition for a generated, computed column type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
     * @return void
=======
     * @param Fluent $column
     * @return mixed
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     *
     */
    protected function typeComputed(Fluent $column)
    {
        throw new RuntimeException('This database driver does not support the computed type.');
    }

    /**
     * Add the column modifiers to the definition.
     *
     * @param string $sql
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function addModifiers($sql, Blueprint $blueprint, Fluent $column): string
    {
        foreach ($this->modifiers as $modifier) {
            if (method_exists($this, $method = "modify{$modifier}")) {
                $sql .= $this->{$method}($blueprint, $column);
            }
        }

        return $sql;
    }

    /**
     * Get the primary key command if it exists on the blueprint.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param string $name
     * @return \Mini\Support\Fluent|null
=======
     * @param Blueprint $blueprint
     * @param string $name
     * @return Fluent|null
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function getCommandByName(Blueprint $blueprint, $name): ?Fluent
    {
        $commands = $this->getCommandsByName($blueprint, $name);

        if (count($commands) > 0) {
            return reset($commands);
        }
        return null;
    }

    /**
     * Get all of the commands with a given name.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
=======
     * @param Blueprint $blueprint
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $name
     * @return array
     */
    protected function getCommandsByName(Blueprint $blueprint, string $name): array
    {
        return array_filter($blueprint->getCommands(), static function ($value) use ($name) {
            return $value->name === $name;
        });
    }

    /**
     * Add a prefix to an array of values.
     *
     * @param string $prefix
     * @param array $values
     * @return array
     */
    public function prefixArray(string $prefix, array $values): array
    {
<<<<<<< HEAD
        return array_map(function ($value) use ($prefix) {
=======
        return array_map(static function ($value) use ($prefix) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            return $prefix . ' ' . $value;
        }, $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param mixed $table
     * @return string
     */
    public function wrapTable($table): string
    {
        return parent::wrapTable(
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }

    /**
     * Wrap a value in keyword identifiers.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Expression|string $value
=======
     * @param Expression|string $value
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param bool $prefixAlias
     * @return string
     */
    public function wrap($value, bool $prefixAlias = false): string
    {
        return parent::wrap(
            $value instanceof Fluent ? $value->name : $value, $prefixAlias
        );
    }

    /**
     * Format a value so that it can be used in "default" clauses.
     *
     * @param mixed $value
     * @return string
     */
    protected function getDefaultValue($value): string
    {
        if ($value instanceof Expression) {
            return (string)$value;
        }

        return is_bool($value)
            ? "'" . (int)$value . "'"
            : "'" . (string)$value . "'";
    }

    /**
     * Create an empty Doctrine DBAL TableDiff from the Blueprint.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
     * @return \Doctrine\DBAL\Schema\TableDiff
=======
     * @param Blueprint $blueprint
     * @param SchemaManager $schema
     * @return TableDiff
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function getDoctrineTableDiff(Blueprint $blueprint, SchemaManager $schema): TableDiff
    {
        $table = $this->getTablePrefix() . $blueprint->getTable();

        return tap(new TableDiff($table), function ($tableDiff) use ($schema, $table) {
            $tableDiff->fromTable = $schema->listTableDetails($table);
        });
    }

    /**
     * Get the fluent commands for the grammar.
     *
     * @return array
     */
    public function getFluentCommands(): array
    {
        return $this->fluentCommands;
    }

    /**
     * Check if this Grammar supports schema changes wrapped in a transaction.
     *
     * @return bool
     */
    public function supportsSchemaTransactions(): bool
    {
        return $this->transactions;
    }
}
