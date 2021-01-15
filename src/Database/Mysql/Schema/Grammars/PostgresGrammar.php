<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema\Grammars;

use Mini\Database\Mysql\Schema\Blueprint;
use Mini\Support\Fluent;

class PostgresGrammar extends Grammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected bool $transactions = true;

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected array $modifiers = ['Collate', 'Increment', 'Nullable', 'Default', 'VirtualAs', 'StoredAs'];

    /**
     * The columns available as serials.
     *
     * @var array
     */
    protected array $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * The commands to be executed outside of create or alter command.
     *
     * @var array
     */
    protected array $fluentCommands = ['Comment'];

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists(): string
    {
        return "select * from information_schema.tables where table_schema = ? and table_name = ? and table_type = 'BASE TABLE'";
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @return string
     */
    public function compileColumnListing(): string
    {
        return 'select column_name from information_schema.columns where table_schema = ? and table_name = ?';
    }

    /**
     * Compile a create table command.
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
    public function compileCreate(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
        );
    }

    /**
     * Compile a column addition command.
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
    public function compileAdd(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf('alter table %s %s',
            $this->wrapTable($blueprint),
            implode(', ', $this->prefixArray('add column', $this->getColumns($blueprint)))
        );
    }

    /**
     * Compile a primary key command.
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
    public function compilePrimary(Blueprint $blueprint, Fluent $command): string
    {
        $columns = $this->columnize($command->columns);

        return 'alter table ' . $this->wrapTable($blueprint) . " add primary key ({$columns})";
    }

    /**
     * Compile a unique key command.
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
    public function compileUnique(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf('alter table %s add constraint %s unique (%s)',
            $this->wrapTable($blueprint),
            $this->wrap($command->index),
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a plain index key command.
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
    public function compileIndex(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf('create index %s on %s%s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
            $command->algorithm ? ' using ' . $command->algorithm : '',
            $this->columnize($command->columns)
        );
    }

    /**
     * Compile a spatial index key command.
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
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command): string
    {
        $command->algorithm = 'gist';

        return $this->compileIndex($blueprint, $command);
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
        $sql = parent::compileForeign($blueprint, $command);

        if (!is_null($command->deferrable)) {
            $sql .= $command->deferrable ? ' deferrable' : ' not deferrable';
        }

        if ($command->deferrable && !is_null($command->initiallyImmediate)) {
            $sql .= $command->initiallyImmediate ? ' initially immediate' : ' initially deferred';
        }

        if (!is_null($command->notValid)) {
            $sql .= ' not valid';
        }

        return $sql;
    }

    /**
     * Compile a drop table command.
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
    public function compileDrop(Blueprint $blueprint, Fluent $command): string
    {
        return 'drop table ' . $this->wrapTable($blueprint);
    }

    /**
     * Compile a drop table (if exists) command.
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
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command): string
    {
        return 'drop table if exists ' . $this->wrapTable($blueprint);
    }

    /**
     * Compile the SQL needed to drop all tables.
     *
     * @param array $tables
     * @return string
     */
    public function compileDropAllTables($tables): string
    {
        return 'drop table "' . implode('","', $tables) . '" cascade';
    }

    /**
     * Compile the SQL needed to drop all views.
     *
     * @param array $views
     * @return string
     */
    public function compileDropAllViews($views): string
    {
        return 'drop view "' . implode('","', $views) . '" cascade';
    }

    /**
     * Compile the SQL needed to drop all types.
     *
     * @param array $types
     * @return string
     */
    public function compileDropAllTypes($types): string
    {
        return 'drop type "' . implode('","', $types) . '" cascade';
    }

    /**
     * Compile the SQL needed to retrieve all table names.
     *
     * @param string|array $schema
     * @return string
     */
    public function compileGetAllTables($schema): string
    {
        return "select tablename from pg_catalog.pg_tables where schemaname in ('" . implode("','", (array)$schema) . "')";
    }

    /**
     * Compile the SQL needed to retrieve all view names.
     *
     * @param string|array $schema
     * @return string
     */
    public function compileGetAllViews($schema): string
    {
        return "select viewname from pg_catalog.pg_views where schemaname in ('" . implode("','", (array)$schema) . "')";
    }

    /**
     * Compile the SQL needed to retrieve all type names.
     *
     * @return string
     */
    public function compileGetAllTypes(): string
    {
        return 'select distinct pg_type.typname from pg_type inner join pg_enum on pg_enum.enumtypid = pg_type.oid';
    }

    /**
     * Compile a drop column command.
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
    public function compileDropColumn(Blueprint $blueprint, Fluent $command): string
    {
        $columns = $this->prefixArray('drop column', $this->wrapArray($command->columns));

        return 'alter table ' . $this->wrapTable($blueprint) . ' ' . implode(', ', $columns);
    }

    /**
     * Compile a drop primary key command.
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
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command): string
    {
        $index = $this->wrap("{$blueprint->getTable()}_pkey");

        return 'alter table ' . $this->wrapTable($blueprint) . " drop constraint {$index}";
    }

    /**
     * Compile a drop unique key command.
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
    public function compileDropUnique(Blueprint $blueprint, Fluent $command): string
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a drop index command.
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
    public function compileDropIndex(Blueprint $blueprint, Fluent $command): string
    {
        return "drop index {$this->wrap($command->index)}";
    }

    /**
     * Compile a drop spatial index command.
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
    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command): string
    {
        return $this->compileDropIndex($blueprint, $command);
    }

    /**
     * Compile a drop foreign key command.
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
    public function compileDropForeign(Blueprint $blueprint, Fluent $command): string
    {
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop constraint {$index}";
    }

    /**
     * Compile a rename table command.
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
    public function compileRename(Blueprint $blueprint, Fluent $command): string
    {
        $from = $this->wrapTable($blueprint);

        return "alter table {$from} rename to " . $this->wrapTable($command->to);
    }

    /**
     * Compile a rename index command.
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
    public function compileRenameIndex(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf('alter index %s rename to %s',
            $this->wrap($command->from),
            $this->wrap($command->to)
        );
    }

    /**
     * Compile the command to enable foreign key constraints.
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints(): string
    {
        return 'SET CONSTRAINTS ALL IMMEDIATE;';
    }

    /**
     * Compile the command to disable foreign key constraints.
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints(): string
    {
        return 'SET CONSTRAINTS ALL DEFERRED;';
    }

    /**
     * Compile a comment command.
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
    public function compileComment(Blueprint $blueprint, Fluent $command): string
    {
        return sprintf('comment on column %s.%s is %s',
            $this->wrapTable($blueprint),
            $this->wrap($command->column->name),
            "'" . str_replace("'", "''", $command->value) . "'"
        );
    }

    /**
     * Create the column definition for a char type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeChar(Fluent $column): string
    {
        return "char({$column->length})";
    }

    /**
     * Create the column definition for a string type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeString(Fluent $column): string
    {
        return "varchar({$column->length})";
    }

    /**
     * Create the column definition for a text type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeText(Fluent $column): string
    {
        return 'text';
    }

    /**
     * Create the column definition for a medium text type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeMediumText(Fluent $column): string
    {
        return 'text';
    }

    /**
     * Create the column definition for a long text type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeLongText(Fluent $column): string
    {
        return 'text';
    }

    /**
     * Create the column definition for an integer type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeInteger(Fluent $column): string
    {
        return $this->generatableColumn('integer', $column);
    }

    /**
     * Create the column definition for a big integer type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeBigInteger(Fluent $column): string
    {
        return $this->generatableColumn('bigint', $column);
    }

    /**
     * Create the column definition for a medium integer type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeMediumInteger(Fluent $column): string
    {
        return $this->generatableColumn('integer', $column);
    }

    /**
     * Create the column definition for a tiny integer type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeTinyInteger(Fluent $column): string
    {
        return $this->generatableColumn('smallint', $column);
    }

    /**
     * Create the column definition for a small integer type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeSmallInteger(Fluent $column): string
    {
        return $this->generatableColumn('smallint', $column);
    }

    /**
     * Create the column definition for a generatable column.
     *
     * @param string $type
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function generatableColumn(string $type, Fluent $column): string
    {
        if (!$column->autoIncrement && is_null($column->generatedAs)) {
            return $type;
        }

        if ($column->autoIncrement && is_null($column->generatedAs)) {
            return with([
                'integer' => 'serial',
                'bigint' => 'bigserial',
                'smallint' => 'smallserial',
            ])[$type];
        }

        $options = '';

        if (!is_bool($column->generatedAs) && !empty($column->generatedAs)) {
            $options = sprintf(' (%s)', $column->generatedAs);
        }

        return sprintf(
            '%s generated %s as identity%s',
            $type,
            $column->always ? 'always' : 'by default',
            $options
        );
    }

    /**
     * Create the column definition for a float type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeFloat(Fluent $column): string
    {
        return $this->typeDouble($column);
    }

    /**
     * Create the column definition for a double type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeDouble(Fluent $column): string
    {
        return 'double precision';
    }

    /**
     * Create the column definition for a real type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeReal(Fluent $column): string
    {
        return 'real';
    }

    /**
     * Create the column definition for a decimal type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeDecimal(Fluent $column): string
    {
        return "decimal({$column->total}, {$column->places})";
    }

    /**
     * Create the column definition for a boolean type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeBoolean(Fluent $column): string
    {
        return 'boolean';
    }

    /**
     * Create the column definition for an enumeration type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeEnum(Fluent $column): string
    {
        return sprintf(
            'varchar(255) check ("%s" in (%s))',
            $column->name,
            $this->quoteString($column->allowed)
        );
    }

    /**
     * Create the column definition for a json type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeJson(Fluent $column): string
    {
        return 'json';
    }

    /**
     * Create the column definition for a jsonb type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeJsonb(Fluent $column): string
    {
        return 'jsonb';
    }

    /**
     * Create the column definition for a date type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeDate(Fluent $column): string
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeDateTime(Fluent $column): string
    {
        return $this->typeTimestamp($column);
    }

    /**
     * Create the column definition for a date-time (with time zone) type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column): string
    {
        return $this->typeTimestampTz($column);
    }

    /**
     * Create the column definition for a time type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeTime(Fluent $column): string
    {
        return 'time' . (is_null($column->precision) ? '' : "($column->precision)") . ' without time zone';
    }

    /**
     * Create the column definition for a time (with time zone) type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeTimeTz(Fluent $column): string
    {
        return 'time' . (is_null($column->precision) ? '' : "($column->precision)") . ' with time zone';
    }

    /**
     * Create the column definition for a timestamp type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeTimestamp(Fluent $column): string
    {
        $columnType = 'timestamp' . (is_null($column->precision) ? '' : "($column->precision)") . ' without time zone';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a timestamp (with time zone) type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeTimestampTz(Fluent $column): string
    {
        $columnType = 'timestamp' . (is_null($column->precision) ? '' : "($column->precision)") . ' with time zone';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
    }

    /**
     * Create the column definition for a year type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeYear(Fluent $column): string
    {
        return $this->typeInteger($column);
    }

    /**
     * Create the column definition for a binary type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeBinary(Fluent $column): string
    {
        return 'bytea';
    }

    /**
     * Create the column definition for a uuid type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeUuid(Fluent $column): string
    {
        return 'uuid';
    }

    /**
     * Create the column definition for an IP address type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeIpAddress(Fluent $column): string
    {
        return 'inet';
    }

    /**
     * Create the column definition for a MAC address type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeMacAddress(Fluent $column): string
    {
        return 'macaddr';
    }

    /**
     * Create the column definition for a spatial Geometry type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeGeometry(Fluent $column): string
    {
        return $this->formatPostGisType('geometry', $column);
    }

    /**
     * Create the column definition for a spatial Point type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typePoint(Fluent $column): string
    {
        return $this->formatPostGisType('point', $column);
    }

    /**
     * Create the column definition for a spatial LineString type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeLineString(Fluent $column): string
    {
        return $this->formatPostGisType('linestring', $column);
    }

    /**
     * Create the column definition for a spatial Polygon type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typePolygon(Fluent $column): string
    {
        return $this->formatPostGisType('polygon', $column);
    }

    /**
     * Create the column definition for a spatial GeometryCollection type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeGeometryCollection(Fluent $column): string
    {
        return $this->formatPostGisType('geometrycollection', $column);
    }

    /**
     * Create the column definition for a spatial MultiPoint type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeMultiPoint(Fluent $column): string
    {
        return $this->formatPostGisType('multipoint', $column);
    }

    /**
     * Create the column definition for a spatial MultiLineString type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    public function typeMultiLineString(Fluent $column): string
    {
        return $this->formatPostGisType('multilinestring', $column);
    }

    /**
     * Create the column definition for a spatial MultiPolygon type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeMultiPolygon(Fluent $column): string
    {
        return $this->formatPostGisType('multipolygon', $column);
    }

    /**
     * Create the column definition for a spatial MultiPolygonZ type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeMultiPolygonZ(Fluent $column): string
    {
        return $this->formatPostGisType('multipolygonz', $column);
    }

    /**
     * Format the column definition for a PostGIS spatial type.
     *
     * @param string $type
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    private function formatPostGisType(string $type, Fluent $column): string
    {
        if ($column->isGeometry === null) {
            return sprintf('geography(%s, %s)', $type, $column->projection ?? '4326');
        }

        if ($column->projection !== null) {
            return sprintf('geometry(%s, %s)', $type, $column->projection);
        }

        return "geometry({$type})";
    }

    /**
     * Get the SQL for a collation column modifier.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function modifyCollate(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->collation)) {
            return ' collate ' . $this->wrapValue($column->collation);
        }
        return null;
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column): string
    {
        return $column->nullable ? ' null' : ' not null';
    }

    /**
     * Get the SQL for a default column modifier.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->default)) {
            return ' default ' . $this->getDefaultValue($column->default);
        }
        return null;
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column): ?string
    {
        if ($column->autoIncrement && (in_array($column->type, $this->serials, true) || ($column->generatedAs !== null))) {
            return ' primary key';
        }
        return null;
    }

    /**
     * Get the SQL for a generated virtual column modifier.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function modifyVirtualAs(Blueprint $blueprint, Fluent $column): ?string
    {
        if ($column->virtualAs !== null) {
            return " generated always as ({$column->virtualAs})";
        }
        return null;
    }

    /**
     * Get the SQL for a generated stored column modifier.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $column
=======
     * @param Blueprint $blueprint
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function modifyStoredAs(Blueprint $blueprint, Fluent $column): ?string
    {
        if ($column->storedAs !== null) {
            return " generated always as ({$column->storedAs}) stored";
        }
        return null;
    }
}
