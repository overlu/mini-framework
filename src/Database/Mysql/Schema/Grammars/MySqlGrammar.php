<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema\Grammars;

use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Schema\Blueprint;
use Mini\Support\Fluent;
use RuntimeException;

class MySqlGrammar extends Grammar
{
    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected array $modifiers = [
        'Unsigned', 'Charset', 'Collate', 'VirtualAs', 'StoredAs', 'Nullable',
        'Srid', 'Default', 'Increment', 'Comment', 'After', 'First',
    ];

    /**
     * The possible column serials.
     *
     * @var array
     */
    protected array $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * Compile the query to determine the list of tables.
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
        return 'select column_name as `column_name` from information_schema.columns where table_schema = ? and table_name = ?';
    }

    /**
     * Compile a create table command.
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
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection): string
    {
        $sql = $this->compileCreateTable(
            $blueprint, $command, $connection
        );

        // Once we have the primary SQL, we can add the encoding option to the SQL for
        // the table.  Then, we can check if a storage engine has been supplied for
        // the table. If so, we will add the engine declaration to the SQL query.
        $sql = $this->compileCreateEncoding(
            $sql, $connection, $blueprint
        );

        // Finally, we will append the engine configuration onto this SQL statement as
        // the final thing we do before returning this finished SQL. Once this gets
        // added the query will be ready to execute against the real connections.
        return $this->compileCreateEngine(
            $sql, $connection, $blueprint
        );
    }

    /**
     * Create the main create table clause.
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
     * @return string
     */
    protected function compileCreateTable(Blueprint $blueprint, Fluent $command, Connection $connection): string
    {
        return sprintf('%s table %s (%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint))
        );
    }

    /**
     * Append the character set specifications to a command.
     *
     * @param string $sql
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Connection $connection
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
=======
     * @param Connection $connection
     * @param Blueprint $blueprint
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileCreateEncoding(string $sql, Connection $connection, Blueprint $blueprint): string
    {
        // First we will set the character set if one has been set on either the create
        // blueprint itself or on the root configuration for the connection that the
        // table is being created on. We will add these to the create table query.
        if (isset($blueprint->charset)) {
            $sql .= ' default character set ' . $blueprint->charset;
        } elseif (!is_null($charset = $connection->getConfig('charset'))) {
            $sql .= ' default character set ' . $charset;
        }

        // Next we will add the collation to the create table statement if one has been
        // added to either this create table blueprint or the configuration for this
        // connection that the query is targeting. We'll add it to this SQL query.
        if (isset($blueprint->collation)) {
            $sql .= " collate '{$blueprint->collation}'";
        } elseif (!is_null($collation = $connection->getConfig('collation'))) {
            $sql .= " collate '{$collation}'";
        }

        return $sql;
    }

    /**
     * Append the engine specifications to a command.
     *
     * @param string $sql
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Connection $connection
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
=======
     * @param Connection $connection
     * @param Blueprint $blueprint
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function compileCreateEngine(string $sql, Connection $connection, Blueprint $blueprint): string
    {
        if (isset($blueprint->engine)) {
            return $sql . ' engine = ' . $blueprint->engine;
<<<<<<< HEAD
        } elseif (!is_null($engine = $connection->getConfig('engine'))) {
=======
        }

        if (!is_null($engine = $connection->getConfig('engine'))) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            return $sql . ' engine = ' . $engine;
        }

        return $sql;
    }

    /**
     * Compile an add column command.
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
        $columns = $this->prefixArray('add', $this->getColumns($blueprint));

        return 'alter table ' . $this->wrapTable($blueprint) . ' ' . implode(', ', $columns);
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
        $command->name(null);

        return $this->compileKey($blueprint, $command, 'primary key');
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
        return $this->compileKey($blueprint, $command, 'unique');
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
        return $this->compileKey($blueprint, $command, 'index');
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
        return $this->compileKey($blueprint, $command, 'spatial index');
    }

    /**
     * Compile an index creation command.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $command
=======
     * @param Blueprint $blueprint
     * @param Fluent $command
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param string $type
     * @return string
     */
    protected function compileKey(Blueprint $blueprint, Fluent $command, string $type): string
    {
        return sprintf('alter table %s add %s %s%s(%s)',
            $this->wrapTable($blueprint),
            $type,
            $this->wrap($command->index),
            $command->algorithm ? ' using ' . $command->algorithm : '',
            $this->columnize($command->columns)
        );
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
        $columns = $this->prefixArray('drop', $this->wrapArray($command->columns));

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
        return 'alter table ' . $this->wrapTable($blueprint) . ' drop primary key';
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

        return "alter table {$this->wrapTable($blueprint)} drop index {$index}";
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
        $index = $this->wrap($command->index);

        return "alter table {$this->wrapTable($blueprint)} drop index {$index}";
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

        return "alter table {$this->wrapTable($blueprint)} drop foreign key {$index}";
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

        return "rename table {$from} to " . $this->wrapTable($command->to);
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
        return sprintf('alter table %s rename index %s to %s',
            $this->wrapTable($blueprint),
            $this->wrap($command->from),
            $this->wrap($command->to)
        );
    }

    /**
     * Compile the SQL needed to drop all tables.
     *
     * @param array $tables
     * @return string
     */
    public function compileDropAllTables(array $tables): string
    {
        return 'drop table ' . implode(',', $this->wrapArray($tables));
    }

    /**
     * Compile the SQL needed to drop all views.
     *
     * @param array $views
     * @return string
     */
    public function compileDropAllViews(array $views): string
    {
        return 'drop view ' . implode(',', $this->wrapArray($views));
    }

    /**
     * Compile the SQL needed to retrieve all table names.
     *
     * @return string
     */
    public function compileGetAllTables(): string
    {
        return 'SHOW FULL TABLES WHERE table_type = \'BASE TABLE\'';
    }

    /**
     * Compile the SQL needed to retrieve all view names.
     *
     * @return string
     */
    public function compileGetAllViews(): string
    {
        return 'SHOW FULL TABLES WHERE table_type = \'VIEW\'';
    }

    /**
     * Compile the command to enable foreign key constraints.
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints(): string
    {
        return 'SET FOREIGN_KEY_CHECKS=1;';
    }

    /**
     * Compile the command to disable foreign key constraints.
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints(): string
    {
        return 'SET FOREIGN_KEY_CHECKS=0;';
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
        return 'mediumtext';
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
        return 'longtext';
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
        return 'bigint';
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
        return 'int';
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
        return 'mediumint';
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
        return 'tinyint';
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
        return 'smallint';
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
        if ($column->total && $column->places) {
            return "double({$column->total}, {$column->places})";
        }

        return 'double';
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
        return 'tinyint(1)';
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
        return sprintf('enum(%s)', $this->quoteString($column->allowed));
    }

    /**
     * Create the column definition for a set enumeration type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
=======
     * @param Fluent $column
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function typeSet(Fluent $column): string
    {
        return sprintf('set(%s)', $this->quoteString($column->allowed));
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
        return 'json';
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
        $columnType = $column->precision ? "datetime($column->precision)" : 'datetime';

        return $column->useCurrent ? "$columnType default CURRENT_TIMESTAMP" : $columnType;
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
        return $this->typeDateTime($column);
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
        return $column->precision ? "time($column->precision)" : 'time';
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
        return $this->typeTime($column);
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
        $columnType = $column->precision ? "timestamp($column->precision)" : 'timestamp';

        $defaultCurrent = $column->precision ? "CURRENT_TIMESTAMP($column->precision)" : 'CURRENT_TIMESTAMP';

        return $column->useCurrent ? "$columnType default $defaultCurrent" : $columnType;
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
        return $this->typeTimestamp($column);
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
        return 'year';
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
        return 'blob';
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
        return 'char(36)';
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
        return 'varchar(45)';
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
        return 'varchar(17)';
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
    public function typeGeometry(Fluent $column): string
    {
        return 'geometry';
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
    public function typePoint(Fluent $column): string
    {
        return 'point';
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
    public function typeLineString(Fluent $column): string
    {
        return 'linestring';
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
    public function typePolygon(Fluent $column): string
    {
        return 'polygon';
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
    public function typeGeometryCollection(Fluent $column): string
    {
        return 'geometrycollection';
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
    public function typeMultiPoint(Fluent $column): string
    {
        return 'multipoint';
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
        return 'multilinestring';
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
    public function typeMultiPolygon(Fluent $column): string
    {
        return 'multipolygon';
    }

    /**
     * Create the column definition for a generated, computed column type.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $column
     * @return void
=======
     * @param Fluent $column
     * @return void|mixed
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     *
     * @throws RuntimeException
     */
    protected function typeComputed(Fluent $column)
    {
        throw new RuntimeException('This database driver requires a type, see the virtualAs / storedAs modifiers.');
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
        if (!is_null($column->virtualAs)) {
            return " as ({$column->virtualAs})";
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
        if (!is_null($column->storedAs)) {
            return " as ({$column->storedAs}) stored";
        }
        return null;
    }

    /**
     * Get the SQL for an unsigned column modifier.
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
    protected function modifyUnsigned(Blueprint $blueprint, Fluent $column): ?string
    {
        if ($column->unsigned) {
            return ' unsigned';
        }
        return null;
    }

    /**
     * Get the SQL for a character set column modifier.
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
    protected function modifyCharset(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->charset)) {
            return ' character set ' . $column->charset;
        }
        return null;
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
            return " collate '{$column->collation}'";
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
    protected function modifyNullable(Blueprint $blueprint, Fluent $column): ?string
    {
        if (is_null($column->virtualAs) && is_null($column->storedAs)) {
            return $column->nullable ? ' null' : ' not null';
        }

        if ($column->nullable === false) {
            return ' not null';
        }
        return null;
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
        if (in_array($column->type, $this->serials, true) && $column->autoIncrement) {
            return ' auto_increment primary key';
        }
        return null;
    }

    /**
     * Get the SQL for a "first" column modifier.
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
    protected function modifyFirst(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->first)) {
            return ' first';
        }
        return null;
    }

    /**
     * Get the SQL for an "after" column modifier.
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
    protected function modifyAfter(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->after)) {
            return ' after ' . $this->wrap($column->after);
        }
        return null;
    }

    /**
     * Get the SQL for a "comment" column modifier.
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
    protected function modifyComment(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->comment)) {
            return " comment '" . addslashes($column->comment) . "'";
        }
        return null;
    }

    /**
     * Get the SQL for a SRID column modifier.
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
    protected function modifySrid(Blueprint $blueprint, Fluent $column): ?string
    {
        if (!is_null($column->srid) && is_int($column->srid) && $column->srid > 0) {
            return ' srid ' . $column->srid;
        }
        return null;
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string $value
     * @return string
     */
    protected function wrapValue(string $value): string
    {
        if ($value !== '*') {
            return '`' . str_replace('`', '``', $value) . '`';
        }

        return $value;
    }
}
