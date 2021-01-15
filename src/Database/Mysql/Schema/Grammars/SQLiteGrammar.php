<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema\Grammars;

use Doctrine\DBAL\Schema\Index;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Schema\Blueprint;
use Mini\Support\Arr;
use Mini\Support\Fluent;
use RuntimeException;

class SQLiteGrammar extends Grammar
{
    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected array $modifiers = ['Nullable', 'Default', 'Increment'];

    /**
     * The columns available as serials.
     *
     * @var array
     */
    protected array $serials = ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger'];

    /**
     * Compile the query to determine if a table exists.
     *
     * @return string
     */
    public function compileTableExists(): string
    {
        return "select * from sqlite_master where type = 'table' and name = ?";
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @param string $table
     * @return string
     */
    public function compileColumnListing($table): string
    {
        return 'pragma table_info(' . $this->wrap(str_replace('.', '__', $table)) . ')';
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
        return sprintf('%s table %s (%s%s%s)',
            $blueprint->temporary ? 'create temporary' : 'create',
            $this->wrapTable($blueprint),
            implode(', ', $this->getColumns($blueprint)),
            (string)$this->addForeignKeys($blueprint),
            (string)$this->addPrimaryKeys($blueprint)
        );
    }

    /**
     * Get the foreign key syntax for a table creation statement.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @return string|null
=======
     * @param Blueprint $blueprint
     * @return string|null|mixed
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function addForeignKeys(Blueprint $blueprint)
    {
        $foreigns = $this->getCommandsByName($blueprint, 'foreign');

        return collect($foreigns)->reduce(function ($sql, $foreign) {
            // Once we have all the foreign key commands for the table creation statement
            // we'll loop through each of them and add them to the create table SQL we
            // are building, since SQLite needs foreign keys on the tables creation.
            $sql .= $this->getForeignKey($foreign);

            if (!is_null($foreign->onDelete)) {
                $sql .= " on delete {$foreign->onDelete}";
            }

            // If this foreign key specifies the action to be taken on update we will add
            // that to the statement here. We'll append it to this SQL and then return
            // the SQL so we can keep adding any other foreign constraints onto this.
            if (!is_null($foreign->onUpdate)) {
                $sql .= " on update {$foreign->onUpdate}";
            }

            return $sql;
        }, '');
    }

    /**
     * Get the SQL for the foreign key.
     *
<<<<<<< HEAD
     * @param \Mini\Support\Fluent $foreign
=======
     * @param Fluent $foreign
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string
     */
    protected function getForeignKey($foreign): string
    {
        // We need to columnize the columns that the foreign key is being defined for
        // so that it is a properly formatted list. Once we have done this, we can
        // return the foreign key SQL declaration to the calling method for use.
        return sprintf(', foreign key(%s) references %s(%s)',
            $this->columnize($foreign->columns),
            $this->wrapTable($foreign->on),
            $this->columnize((array)$foreign->references)
        );
    }

    /**
     * Get the primary key syntax for a table creation statement.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
=======
     * @param Blueprint $blueprint
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return string|null
     */
    protected function addPrimaryKeys(Blueprint $blueprint): ?string
    {
        if (!is_null($primary = $this->getCommandByName($blueprint, 'primary'))) {
            return ", primary key ({$this->columnize($primary->columns)})";
        }
        return null;
    }

    /**
     * Compile alter table commands for adding columns.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
     * @param \Mini\Support\Fluent $command
=======
     * @param Blueprint $blueprint
     * @param Fluent $command
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return array
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command): array
    {
        $columns = $this->prefixArray('add column', $this->getColumns($blueprint));

        return collect($columns)->map(function ($column) use ($blueprint) {
            return 'alter table ' . $this->wrapTable($blueprint) . ' ' . $column;
        })->all();
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
        return sprintf('create unique index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
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
        return sprintf('create index %s on %s (%s)',
            $this->wrap($command->index),
            $this->wrapTable($blueprint),
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
     * @return void
     *
     * @throws RuntimeException
     */
    public function compileSpatialIndex(Blueprint $blueprint, Fluent $command): void
    {
        throw new RuntimeException('The database driver in use does not support spatial indexes.');
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
        // Handled on table creation...
        return '';
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
     * @return string
     */
    public function compileDropAllTables(): string
    {
        return "delete from sqlite_master where type in ('table', 'index', 'trigger')";
    }

    /**
     * Compile the SQL needed to drop all views.
     *
     * @return string
     */
    public function compileDropAllViews(): string
    {
        return "delete from sqlite_master where type in ('view')";
    }

    /**
     * Compile the SQL needed to rebuild the database.
     *
     * @return string
     */
    public function compileRebuild(): string
    {
        return 'vacuum';
    }

    /**
     * Compile a drop column command.
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
    public function compileDropColumn(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        $tableDiff = $this->getDoctrineTableDiff(
            $blueprint, $schema = $connection->getDoctrineSchemaManager()
        );

        foreach ($command->columns as $name) {
            $tableDiff->removedColumns[$name] = $connection->getDoctrineColumn(
                $this->getTablePrefix() . $blueprint->getTable(), $name
            );
        }

        return (array)$schema->getDatabasePlatform()->getAlterTableSQL($tableDiff);
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

        return "drop index {$index}";
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

        return "drop index {$index}";
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
     * @return void
     *
     * @throws RuntimeException
     */
    public function compileDropSpatialIndex(Blueprint $blueprint, Fluent $command): void
    {
        throw new RuntimeException('The database driver in use does not support spatial indexes.');
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
    public function compileRenameIndex(Blueprint $blueprint, Fluent $command, Connection $connection): array
    {
        $schemaManager = $connection->getDoctrineSchemaManager();

        $indexes = $schemaManager->listTableIndexes($this->getTablePrefix() . $blueprint->getTable());

        $index = Arr::get($indexes, $command->from);

        if (!$index) {
            throw new RuntimeException("Index [{$command->from}] does not exist.");
        }

        $newIndex = new Index(
            $command->to, $index->getColumns(), $index->isUnique(),
            $index->isPrimary(), $index->getFlags(), $index->getOptions()
        );

        $platform = $schemaManager->getDatabasePlatform();

        return [
            $platform->getDropIndexSQL($command->from, $this->getTablePrefix() . $blueprint->getTable()),
            $platform->getCreateIndexSQL($newIndex, $this->getTablePrefix() . $blueprint->getTable()),
        ];
    }

    /**
     * Compile the command to enable foreign key constraints.
     *
     * @return string
     */
    public function compileEnableForeignKeyConstraints(): string
    {
        return 'PRAGMA foreign_keys = ON;';
    }

    /**
     * Compile the command to disable foreign key constraints.
     *
     * @return string
     */
    public function compileDisableForeignKeyConstraints(): string
    {
        return 'PRAGMA foreign_keys = OFF;';
    }

    /**
     * Compile the SQL needed to enable a writable schema.
     *
     * @return string
     */
    public function compileEnableWriteableSchema(): string
    {
        return 'PRAGMA writable_schema = 1;';
    }

    /**
     * Compile the SQL needed to disable a writable schema.
     *
     * @return string
     */
    public function compileDisableWriteableSchema(): string
    {
        return 'PRAGMA writable_schema = 0;';
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
        return 'varchar';
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
        return 'varchar';
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
     * Create the column definition for a integer type.
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
        return 'integer';
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
        return 'integer';
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
        return 'integer';
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
        return 'integer';
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
        return 'integer';
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
        return 'float';
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
        return 'float';
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
        return 'numeric';
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
        return sprintf(
            'varchar check ("%s" in (%s))',
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
        return 'text';
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
        return 'text';
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
     * Note: "SQLite does not have a storage class set aside for storing dates and/or times."
     * @link https://www.sqlite.org/datatype3.html
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
        return 'time';
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
        return $column->useCurrent ? 'datetime default CURRENT_TIMESTAMP' : 'datetime';
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
        return 'varchar';
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
        return 'varchar';
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
        return 'varchar';
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
        if ($column->autoIncrement && in_array($column->type, $this->serials, true)) {
            return ' primary key autoincrement';
        }
        return null;
    }
}
