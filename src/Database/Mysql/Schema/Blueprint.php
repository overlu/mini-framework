<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema;

use BadMethodCallException;
use Closure;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Query\Expression;
use Mini\Database\Mysql\Schema\Grammars\Grammar;
use Mini\Database\Mysql\SQLiteConnection;
use Mini\Support\Collection;
use Mini\Support\Fluent;
use Mini\Support\Traits\Macroable;

class Blueprint
{
    use Macroable;

    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected string $table;

    /**
     * The prefix of the table.
     *
     * @var string
     */
    protected string $prefix;

    /**
     * The columns that should be added to the table.
     *
     * @var ColumnDefinition[]
     */
    protected array $columns = [];

    /**
     * The commands that should be run for the table.
     *
     * @var Fluent[]
     */
    protected array $commands = [];

    /**
     * The storage engine that should be used for the table.
     *
     * @var string
     */
    public string $engine;

    /**
     * The default character set that should be used for the table.
     *
     * @var string
     */
    public string $charset;

    /**
     * The collation that should be used for the table.
     *
     * @var string
     */
    public string $collation;

    /**
     * Whether to make the table temporary.
     *
     * @var bool
     */
    public bool $temporary = false;

    /**
     * Create a new schema blueprint.
     *
     * @param string $table
     * @param Closure|null $callback
     * @param string $prefix
     * @return void
     */
    public function __construct(string $table, Closure $callback = null, string $prefix = '')
    {
        $this->table = $table;
        $this->prefix = $prefix;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param Connection $connection
     * @param Grammar $grammar
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar): void
    {
        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->statement($statement);
        }
    }

    /**
     * Get the raw SQL statements for the blueprint.
     *
     * @param Connection $connection
     * @param Grammar $grammar
     * @return array
     */
    public function toSql(Connection $connection, Grammar $grammar): array
    {
        $this->addImpliedCommands($grammar);

        $statements = [];

        // Each type of command has a corresponding compiler function on the schema
        // grammar which is used to build the necessary SQL statements to build
        // the blueprint element, so we'll just call that compilers function.
        $this->ensureCommandsAreValid($connection);

        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command->name);

            if (method_exists($grammar, $method) || $grammar::hasMacro($method)) {
                if (!is_null($sql = $grammar->$method($this, $command, $connection))) {
                    $statements = array_merge($statements, (array)$sql);
                }
            }
        }

        return $statements;
    }

    /**
     * Ensure the commands on the blueprint are valid for the connection type.
     *
     * @param Connection $connection
     * @return void
     *
     * @throws BadMethodCallException
     */
    protected function ensureCommandsAreValid(Connection $connection): void
    {
        if ($connection instanceof SQLiteConnection) {
            if ($this->commandsNamed(['dropColumn', 'renameColumn'])->count() > 1) {
                throw new BadMethodCallException(
                    "SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification."
                );
            }

            if ($this->commandsNamed(['dropForeign'])->count() > 0) {
                throw new BadMethodCallException(
                    "SQLite doesn't support dropping foreign keys (you would need to re-create the table)."
                );
            }
        }
    }

    /**
     * Get all of the commands matching the given names.
     *
     * @param array $names
     * @return Collection
     */
    protected function commandsNamed(array $names): Collection
    {
        return collect($this->commands)->filter(static function ($command) use ($names) {
            return in_array($command->name, $names, true);
        });
    }

    /**
     * Add the commands that are implied by the blueprint's state.
     *
     * @param Grammar $grammar
     * @return void
     */
    protected function addImpliedCommands(Grammar $grammar): void
    {
        if (!$this->creating() && count($this->getAddedColumns()) > 0) {
            array_unshift($this->commands, $this->createCommand('add'));
        }

        if (!$this->creating() && count($this->getChangedColumns()) > 0) {
            array_unshift($this->commands, $this->createCommand('change'));
        }

        $this->addFluentIndexes();

        $this->addFluentCommands($grammar);
    }

    /**
     * Add the index commands fluently specified on columns.
     *
     * @return void
     */
    protected function addFluentIndexes(): void
    {
        foreach ($this->columns as $column) {
            foreach (['primary', 'unique', 'index', 'spatialIndex'] as $index) {
                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
                if ($column->{$index} === true) {
                    $this->{$index}($column->name);
                    $column->{$index} = false;

                    continue 2;
                }

                if (isset($column->{$index})) {
                    $this->{$index}($column->name, $column->{$index});
                    $column->{$index} = false;

                    continue 2;
                }

                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
            }
        }
    }

    /**
     * Add the fluent commands specified on any columns.
     *
     * @param Grammar $grammar
     * @return void
     */
    public function addFluentCommands(Grammar $grammar): void
    {
        foreach ($this->columns as $column) {
            foreach ($grammar->getFluentCommands() as $commandName) {
                $attributeName = lcfirst($commandName);

                if (!isset($column->{$attributeName})) {
                    continue;
                }

                $value = $column->{$attributeName};

                $this->addCommand(
                    $commandName, compact('value', 'column')
                );
            }
        }
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function creating(): bool
    {
        return collect($this->commands)->contains(static function ($command) {
            return $command->name === 'create';
        });
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return Fluent
     */
    public function create(): Fluent
    {
        return $this->addCommand('create');
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * @return void
     */
    public function temporary(): void
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return Fluent
     */
    public function drop(): Fluent
    {
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return Fluent
     */
    public function dropIfExists(): Fluent
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param array|mixed $columns
     * @return Fluent
     */
    public function dropColumn($columns): Fluent
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        return $this->addCommand('dropColumn', compact('columns'));
    }

    /**
     * Indicate that the given columns should be renamed.
     *
     * @param string $from
     * @param string $to
     * @return Fluent
     */
    public function renameColumn(string $from, string $to): Fluent
    {
        return $this->addCommand('renameColumn', compact('from', 'to'));
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param string|array|null $index
     * @return Fluent
     */
    public function dropPrimary($index = null): Fluent
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param string|array $index
     * @return Fluent
     */
    public function dropUnique($index): Fluent
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param string|array $index
     * @return Fluent
     */
    public function dropIndex($index): Fluent
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given spatial index should be dropped.
     *
     * @param string|array $index
     * @return Fluent
     */
    public function dropSpatialIndex($index): Fluent
    {
        return $this->dropIndexCommand('dropSpatialIndex', 'spatialIndex', $index);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param string|array $index
     * @return Fluent
     */
    public function dropForeign($index): Fluent
    {
        return $this->dropIndexCommand('dropForeign', 'foreign', $index);
    }

    /**
     * Indicate that the given indexes should be renamed.
     *
     * @param string $from
     * @param string $to
     * @return Fluent
     */
    public function renameIndex(string $from, string $to): Fluent
    {
        return $this->addCommand('renameIndex', compact('from', 'to'));
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestamps(): void
    {
        $this->dropColumn('created_at', 'updated_at');
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestampsTz(): void
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @param string $column
     * @return void
     */
    public function dropSoftDeletes(string $column = 'deleted_at'): void
    {
        $this->dropColumn($column);
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @param string $column
     * @return void
     */
    public function dropSoftDeletesTz(string $column = 'deleted_at'): void
    {
        $this->dropSoftDeletes($column);
    }

    /**
     * Indicate that the remember token column should be dropped.
     *
     * @return void
     */
    public function dropRememberToken(): void
    {
        $this->dropColumn('remember_token');
    }

    /**
     * Indicate that the polymorphic columns should be dropped.
     *
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function dropMorphs(string $name, ?string $indexName = null): void
    {
        $this->dropIndex($indexName ?: $this->createIndexName('index', ["{$name}_type", "{$name}_id"]));

        $this->dropColumn("{$name}_type", "{$name}_id");
    }

    /**
     * Rename the table to a given name.
     *
     * @param string $to
     * @return Fluent
     */
    public function rename(string $to): Fluent
    {
        return $this->addCommand('rename', compact('to'));
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @param string|null $algorithm
     * @return Fluent
     */
    public function primary($columns, ?string $name = null, ?string $algorithm = null): Fluent
    {
        return $this->indexCommand('primary', $columns, $name, $algorithm);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @param string|null $algorithm
     * @return Fluent
     */
    public function unique($columns, ?string $name = null, ?string $algorithm = null): Fluent
    {
        return $this->indexCommand('unique', $columns, $name, $algorithm);
    }

    /**
     * Specify an index for the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @param string|null $algorithm
     * @return Fluent
     */
    public function index($columns, ?string $name = null, ?string $algorithm = null): Fluent
    {
        return $this->indexCommand('index', $columns, $name, $algorithm);
    }

    /**
     * Specify a spatial index for the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @return Fluent
     */
    public function spatialIndex($columns, ?string $name = null): Fluent
    {
        return $this->indexCommand('spatialIndex', $columns, $name);
    }

    /**
     * Specify a raw index for the table.
     *
     * @param string $expression
     * @param string $name
     * @return Fluent
     */
    public function rawIndex($expression, string $name): Fluent
    {
        return $this->index([new Expression($expression)], $name);
    }

    /**
     * Specify a foreign key for the table.
     *
     * @param string|array $columns
     * @param string|null $name
     * @return ForeignKeyDefinition
     */
    public function foreign($columns, ?string $name = null): ForeignKeyDefinition
    {
        $command = new ForeignKeyDefinition(
            $this->indexCommand('foreign', $columns, $name)->getAttributes()
        );

        $this->commands[count($this->commands) - 1] = $command;

        return $command;
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function increments(string $column): ColumnDefinition
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function integerIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function tinyIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedTinyInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function smallIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function mediumIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
     *
     * @param string $column
     * @param int|null $length
     * @return ColumnDefinition
     */
    public function char(string $column, ?int $length = null): ColumnDefinition
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('char', $column, compact('length'));
    }

    /**
     * Create a new string column on the table.
     *
     * @param string $column
     * @param int|null $length
     * @return ColumnDefinition
     */
    public function string(string $column, ?int $length = null): ColumnDefinition
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Create a new text column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new medium text column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function mediumText(string $column): ColumnDefinition
    {
        return $this->addColumn('mediumText', $column);
    }

    /**
     * Create a new long text column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn('longText', $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('integer', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('tinyInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function smallInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('smallInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('mediumInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('bigInteger', $column, compact('autoIncrement', 'unsigned'));
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedTinyInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedSmallInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedMediumInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param string $column
     * @param bool $autoIncrement
     * @return ColumnDefinition
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): ColumnDefinition
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param string $column
     * @return ForeignIdColumnDefinition
     */
    public function foreignId(string $column): ForeignIdColumnDefinition
    {
        $this->columns[] = $column = new ForeignIdColumnDefinition($this, [
            'type' => 'bigInteger',
            'name' => $column,
            'autoIncrement' => false,
            'unsigned' => true,
        ]);

        return $column;
    }

    /**
     * Create a new float column on the table.
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function float(string $column, int $total = 8, int $places = 2, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('float', $column, compact('total', 'places', 'unsigned'));
    }

    /**
     * Create a new double column on the table.
     *
     * @param string $column
     * @param int|null $total
     * @param int|null $places
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function double(string $column, ?int $total = null, ?int $places = null, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('double', $column, compact('total', 'places', 'unsigned'));
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function decimal(string $column, int $total = 8, int $places = 2, bool $unsigned = false): ColumnDefinition
    {
        return $this->addColumn('decimal', $column, compact('total', 'places', 'unsigned'));
    }

    /**
     * Create a new unsigned float column on the table.
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function unsignedFloat(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->float($column, $total, $places, true);
    }

    /**
     * Create a new unsigned double column on the table.
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function unsignedDouble(string $column, ?int $total = null, ?int $places = null): ColumnDefinition
    {
        return $this->double($column, $total, $places, true);
    }

    /**
     * Create a new unsigned decimal column on the table.
     *
     * @param string $column
     * @param int $total
     * @param int $places
     * @return ColumnDefinition
     */
    public function unsignedDecimal(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->decimal($column, $total, $places, true);
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn('boolean', $column);
    }

    /**
     * Create a new enum column on the table.
     *
     * @param string $column
     * @param array $allowed
     * @return ColumnDefinition
     */
    public function enum(string $column, array $allowed): ColumnDefinition
    {
        return $this->addColumn('enum', $column, compact('allowed'));
    }

    /**
     * Create a new set column on the table.
     *
     * @param string $column
     * @param array $allowed
     * @return ColumnDefinition
     */
    public function set(string $column, array $allowed): ColumnDefinition
    {
        return $this->addColumn('set', $column, compact('allowed'));
    }

    /**
     * Create a new json column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn('json', $column);
    }

    /**
     * Create a new jsonb column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function jsonb(string $column): ColumnDefinition
    {
        return $this->addColumn('jsonb', $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn('date', $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function dateTime(string $column, int $precision = 0): ColumnDefinition
    {
        return $this->addColumn('dateTime', $column, compact('precision'));
    }

    /**
     * Create a new date-time column (with time zone) on the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function dateTimeTz(string $column, int $precision = 0): ColumnDefinition
    {
        return $this->addColumn('dateTimeTz', $column, compact('precision'));
    }

    /**
     * Create a new time column on the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function time(string $column, int $precision = 0): ColumnDefinition
    {
        return $this->addColumn('time', $column, compact('precision'));
    }

    /**
     * Create a new time column (with time zone) on the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function timeTz(string $column, int $precision = 0): ColumnDefinition
    {
        return $this->addColumn('timeTz', $column, compact('precision'));
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function timestamp(string $column, int $precision = 0): ColumnDefinition
    {
        return $this->addColumn('timestamp', $column, compact('precision'));
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function timestampTz(string $column, int $precision = 0): ColumnDefinition
    {
        return $this->addColumn('timestampTz', $column, compact('precision'));
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param int $precision
     * @return void
     */
    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision)->nullable();

        $this->timestamp('updated_at', $precision)->nullable();
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * Alias for self::timestamps().
     *
     * @param int $precision
     * @return void
     */
    public function nullableTimestamps(int $precision = 0): void
    {
        $this->timestamps($precision);
    }

    /**
     * Add creation and update timestampTz columns to the table.
     *
     * @param int $precision
     * @return void
     */
    public function timestampsTz(int $precision = 0): void
    {
        $this->timestampTz('created_at', $precision)->nullable();

        $this->timestampTz('updated_at', $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function softDeletes(string $column = 'deleted_at', int $precision = 0): ColumnDefinition
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     *
     * @param string $column
     * @param int $precision
     * @return ColumnDefinition
     */
    public function softDeletesTz(string $column = 'deleted_at', int $precision = 0): ColumnDefinition
    {
        return $this->timestampTz($column, $precision)->nullable();
    }

    /**
     * Create a new year column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function year(string $column): ColumnDefinition
    {
        return $this->addColumn('year', $column);
    }

    /**
     * Create a new binary column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function binary(string $column): ColumnDefinition
    {
        return $this->addColumn('binary', $column);
    }

    /**
     * Create a new uuid column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function uuid(string $column): ColumnDefinition
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Create a new UUID column on the table with a foreign key constraint.
     *
     * @param string $column
     * @return ForeignIdColumnDefinition
     */
    public function foreignUuid(string $column): ForeignIdColumnDefinition
    {
        return $this->columns[] = new ForeignIdColumnDefinition($this, [
            'type' => 'uuid',
            'name' => $column,
        ]);
    }

    /**
     * Create a new IP address column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function ipAddress(string $column): ColumnDefinition
    {
        return $this->addColumn('ipAddress', $column);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function macAddress(string $column): ColumnDefinition
    {
        return $this->addColumn('macAddress', $column);
    }

    /**
     * Create a new geometry column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function geometry(string $column): ColumnDefinition
    {
        return $this->addColumn('geometry', $column);
    }

    /**
     * Create a new point column on the table.
     *
     * @param string $column
     * @param int|null $srid
     * @return ColumnDefinition
     */
    public function point(string $column, ?int $srid = null): ColumnDefinition
    {
        return $this->addColumn('point', $column, compact('srid'));
    }

    /**
     * Create a new linestring column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function lineString(string $column): ColumnDefinition
    {
        return $this->addColumn('linestring', $column);
    }

    /**
     * Create a new polygon column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function polygon(string $column): ColumnDefinition
    {
        return $this->addColumn('polygon', $column);
    }

    /**
     * Create a new geometrycollection column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function geometryCollection(string $column): ColumnDefinition
    {
        return $this->addColumn('geometrycollection', $column);
    }

    /**
     * Create a new multipoint column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function multiPoint(string $column): ColumnDefinition
    {
        return $this->addColumn('multipoint', $column);
    }

    /**
     * Create a new multilinestring column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function multiLineString(string $column): ColumnDefinition
    {
        return $this->addColumn('multilinestring', $column);
    }

    /**
     * Create a new multipolygon column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function multiPolygon(string $column): ColumnDefinition
    {
        return $this->addColumn('multipolygon', $column);
    }

    /**
     * Create a new multipolygon column on the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function multiPolygonZ(string $column): ColumnDefinition
    {
        return $this->addColumn('multipolygonz', $column);
    }

    /**
     * Create a new generated, computed column on the table.
     *
     * @param string $column
     * @param string $expression
     * @return ColumnDefinition
     */
    public function computed(string $column, string $expression): ColumnDefinition
    {
        return $this->addColumn('computed', $column, compact('expression'));
    }

    /**
     * Add the proper columns for a polymorphic table.
     *
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function morphs(string $name, ?string $indexName = null): void
    {
        $this->string("{$name}_type");

        $this->unsignedBigInteger("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table.
     *
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function nullableMorphs(string $name, ?string $indexName = null): void
    {
        $this->string("{$name}_type")->nullable();

        $this->unsignedBigInteger("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add the proper columns for a polymorphic table using UUIDs.
     *
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function uuidMorphs(string $name, ?string $indexName = null): void
    {
        $this->string("{$name}_type");

        $this->uuid("{$name}_id");

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table using UUIDs.
     *
     * @param string $name
     * @param string|null $indexName
     * @return void
     */
    public function nullableUuidMorphs(string $name, ?string $indexName = null): void
    {
        $this->string("{$name}_type")->nullable();

        $this->uuid("{$name}_id")->nullable();

        $this->index(["{$name}_type", "{$name}_id"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     *
     * @return ColumnDefinition
     */
    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add a new index command to the blueprint.
     *
     * @param string $type
     * @param string|array $columns
     * @param string $index
     * @param string|null $algorithm
     * @return Fluent
     */
    protected function indexCommand(string $type, $columns, string $index, ?string $algorithm = null): Fluent
    {
        $columns = (array)$columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        $index = $index ?: $this->createIndexName($type, $columns);

        return $this->addCommand(
            $type, compact('index', 'columns', 'algorithm')
        );
    }

    /**
     * Create a new drop index command on the blueprint.
     *
     * @param string $command
     * @param string $type
     * @param string|array $index
     * @return Fluent
     */
    protected function dropIndexCommand(string $command, string $type, $index): Fluent
    {
        $columns = [];

        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
        if (is_array($index)) {
            $index = $this->createIndexName($type, $columns = $index);
        }

        return $this->indexCommand($command, $columns, $index);
    }

    /**
     * Create a default index name for the table.
     *
     * @param string $type
     * @param array $columns
     * @return string
     */
    protected function createIndexName(string $type, array $columns): string
    {
        $index = strtolower($this->prefix . $this->table . '_' . implode('_', $columns) . '_' . $type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return ColumnDefinition
     */
    public function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        $this->columns[] = $column = new ColumnDefinition(
            array_merge(compact('type', 'name'), $parameters)
        );

        return $column;
    }

    /**
     * Remove a column from the schema blueprint.
     *
     * @param string $name
     * @return $this
     */
    public function removeColumn(string $name): self
    {
        $this->columns = array_values(array_filter($this->columns, static function ($c) use ($name) {
            return $c['name'] !== $name;
        }));

        return $this;
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param string $name
     * @param array $parameters
     * @return Fluent
     */
    protected function addCommand(string $name, array $parameters = []): Fluent
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param string $name
     * @param array $parameters
     * @return Fluent
     */
    protected function createCommand(string $name, array $parameters = []): Fluent
    {
        return new Fluent(array_merge(compact('name'), $parameters));
    }

    /**
     * Get the table the blueprint describes.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the columns on the blueprint.
     *
     * @return ColumnDefinition[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return Fluent[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get the columns on the blueprint that should be added.
     *
     * @return ColumnDefinition[]
     */
    public function getAddedColumns(): array
    {
        return array_filter($this->columns, static function ($column) {
            return !$column->change;
        });
    }

    /**
     * Get the columns on the blueprint that should be changed.
     *
     * @return ColumnDefinition[]
     */
    public function getChangedColumns(): array
    {
        return array_filter($this->columns, static function ($column) {
            return (bool)$column->change;
        });
    }
}
