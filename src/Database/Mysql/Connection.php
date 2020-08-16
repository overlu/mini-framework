<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Closure;
use DateTimeInterface;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Exception;
use Generator;
use Mini\Contracts\Events\Dispatcher;
use Mini\Database\Mysql\Events\QueryExecuted;
use Mini\Database\Mysql\Events\StatementPrepared;
use Mini\Database\Mysql\Events\TransactionBeginning;
use Mini\Database\Mysql\Events\TransactionCommitted;
use Mini\Database\Mysql\Events\TransactionRolledBack;
use Mini\Database\Mysql\Query\Builder as QueryBuilder;
use Mini\Database\Mysql\Query\Expression;
use Mini\Database\Mysql\Query\Grammars\Grammar as QueryGrammar;
use Mini\Database\Mysql\Query\Processors\Processor;
use Mini\Database\Mysql\Schema\Builder as SchemaBuilder;
use Mini\Support\Arr;
use LogicException;
use PDO;
use PDOStatement;

class Connection implements ConnectionInterface
{
    use DetectsConcurrencyErrors,
        DetectsLostConnections,
        Concerns\ManagesTransactions;

    /**
     * The active PDO connection.
     *
     * @var PDO|Closure
     */
    protected $pdo;

    /**
     * The active PDO connection used for reads.
     *
     * @var PDO|Closure
     */
    protected $readPdo;

    /**
     * The name of the connected database.
     *
     * @var string|null
     */
    protected ?string $database = null;

    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected string $tablePrefix = '';

    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected array $config = [];

    /**
     * The reconnector instance for the connection.
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * The query grammar implementation.
     *
     * @var QueryGrammar
     */
    protected ?QueryGrammar $queryGrammar = null;

    /**
     * The schema grammar implementation.
     *
     * @var \Mini\Database\Mysql\Schema\Grammars\Grammar
     */
    protected Schema\Grammars\Grammar $schemaGrammar;

    /**
     * The query post processor implementation.
     *
     * @var Processor
     */
    protected ?Processor $postProcessor = null;

    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected ?Dispatcher $events = null;

    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    protected int $fetchMode = PDO::FETCH_OBJ;

    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected int $transactions = 0;

    /**
     * Indicates if changes have been made to the database.
     *
     * @var int
     */
    protected $recordsModified = false;

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected array $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected bool $loggingQueries = false;

    /**
     * Indicates if the connection is in a "dry run".
     *
     * @var bool
     */
    protected bool $pretending = false;

    /**
     * The instance of Doctrine connection.
     *
     * @var DoctrineConnection
     */
    protected ?DoctrineConnection $doctrineConnection = null;

    /**
     * The connection resolvers.
     *
     * @var array
     */
    protected static array $resolvers = [];

    /**
     * Create a new database connection instance.
     *
     * @param PDO|Closure $pdo
     * @param string $database
     * @param string $tablePrefix
     * @param array $config
     * @return void
     */
    public function __construct($pdo, string $database = '', string $tablePrefix = '', array $config = [])
    {
        $this->pdo = $pdo;

        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultQueryGrammar(): void
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return QueryGrammar|mixed
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }

    /**
     * Set the schema grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultSchemaGrammar(): void
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Mini\Database\Mysql\Schema\Grammars\Grammar|mixed
     */
    protected function getDefaultSchemaGrammar()
    {
        //
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor(): void
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return Processor|mixed
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return SchemaBuilder|mixed
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param Closure|QueryBuilder|string $table
     * @param string|null $as
     * @return QueryBuilder
     */
    public function table($table, ?string $as = null): QueryBuilder
    {
        return $this->query()->from($table, $as);
    }

    /**
     * Get a new query builder instance.
     *
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return mixed
     */
    public function selectOne(string $query, array $bindings = [], bool $useReadPdo = true)
    {
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function selectFromWriteConnection(string $query, array $bindings = []): array
    {
        return $this->select($query, $bindings, false);
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return array
     */
    public function select(string $query, array $bindings = [], bool $useReadPdo = true): array
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $statement = $this->prepared(
                $this->getPdoForSelect($useReadPdo)->prepare($query)
            );

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();
        });
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return Generator
     */
    public function cursor(string $query, array $bindings = [], bool $useReadPdo = true): Generator
    {
        $statement = $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // First we will create a statement for the query. Then, we will set the fetch
            // mode and prepare the bindings for the query. Once that's done we will be
            // ready to execute the query against the database and return the cursor.
            $statement = $this->prepared($this->getPdoForSelect($useReadPdo)
                ->prepare($query));

            $this->bindValues(
                $statement, $this->prepareBindings($bindings)
            );

            // Next, we'll execute the query against the database and return the statement
            // so we can return the cursor. The cursor will use a PHP generator to give
            // back one row at a time without using a bunch of memory to render them.
            $statement->execute();

            return $statement;
        });

        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /**
     * Configure the PDO prepared statement.
     *
     * @param PDOStatement $statement
     * @return PDOStatement
     */
    protected function prepared(PDOStatement $statement): PDOStatement
    {
        $statement->setFetchMode($this->fetchMode);

        $this->event(new StatementPrepared(
            $this, $statement
        ));

        return $statement;
    }

    /**
     * Get the PDO connection to use for a select query.
     *
     * @param bool $useReadPdo
     * @return PDO
     */
    protected function getPdoForSelect(bool $useReadPdo = true): PDO
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $this->recordsHaveBeenModified();

            return $statement->execute();
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $this->recordsHaveBeenModified(
                ($count = $statement->rowCount()) > 0
            );

            return $count;
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param string $query
     * @return bool
     */
    public function unprepared(string $query): bool
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            $this->recordsHaveBeenModified(
                $change = $this->getPdo()->exec($query) !== false
            );

            return $change;
        });
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param Closure $callback
     * @return array
     */
    public function pretend(Closure $callback): array
    {
        return $this->withFreshQueryLog(function () use ($callback) {
            $this->pretending = true;

            // Basically to make the database connection "pretend", we will just return
            // the default values for all the query methods, then we will return an
            // array of queries that were "executed" within the Closure callback.
            $callback($this);

            $this->pretending = false;

            return $this->queryLog;
        });
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param Closure $callback
     * @return array
     */
    protected function withFreshQueryLog(Closure $callback): array
    {
        $loggingQueries = $this->loggingQueries;

        // First we will back up the value of the logging queries property and then
        // we'll be ready to run callbacks. This query log will also get cleared
        // so we will have a new log of all the queries that are executed now.
        $this->enableQueryLog();

        $this->queryLog = [];

        // Now we'll execute this callback and capture the result. Once it has been
        // executed we will restore the value of query logging and give back the
        // value of the callback so the original callers can have the results.
        $result = $callback();

        $this->loggingQueries = $loggingQueries;

        return $result;
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param PDOStatement $statement
     * @param array $bindings
     * @return void
     */
    public function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings): array
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = (int)$value;
            }
        }

        return $bindings;
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function run(string $query, array $bindings, Closure $callback)
    {
        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $e) {
            $result = $this->handleQueryException(
                $e, $query, $bindings, $callback
            );
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $this->logQuery(
            $query, $bindings, $this->getElapsedTime($start)
        );

        return $result;
    }

    /**
     * Run a SQL statement.
     *
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function runQueryCallback(string $query, array $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            $result = $callback($query, $bindings);
        }

            // If an exception occurs when attempting to run a query, we'll format the error
            // message to include the bindings with SQL, which will make this exception a
            // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($bindings), $e
            );
        }

        return $result;
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param string $query
     * @param array $bindings
     * @param float|null $time
     * @return void
     */
    public function logQuery(string $query, array $bindings, ?float $time = null): void
    {
        $this->event(new QueryExecuted($query, $bindings, $time, $this));

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param int $start
     * @return float
     */
    protected function getElapsedTime($start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Handle a query exception.
     *
     * @param QueryException $e
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function handleQueryException(QueryException $e, string $query, array $bindings, Closure $callback)
    {
        if ($this->transactions >= 1) {
            throw $e;
        }

        return $this->tryAgainIfCausedByLostConnection(
            $e, $query, $bindings, $callback
        );
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param QueryException $e
     * @param string $query
     * @param array $bindings
     * @param Closure $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, string $query, array $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Reconnect to the database.
     *
     * @return void
     *
     * @throws LogicException
     */
    public function reconnect(): void
    {
        if (is_callable($this->reconnector)) {
            $this->doctrineConnection = null;

            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection(): void
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    /**
     * Register a database query listener with the connection.
     *
     * @param Closure $callback
     * @return void
     */
    public function listen(Closure $callback): void
    {
        if (isset($this->events)) {
            $this->events->listen(Events\QueryExecuted::class, $callback);
        }
    }

    /**
     * Fire an event for this connection.
     *
     * @param string $event
     * @return array|null
     */
    protected function fireConnectionEvent(string $event): ?array
    {
        if (!isset($this->events)) {
            return null;
        }

        switch ($event) {
            case 'beganTransaction':
                return $this->events->dispatch(new TransactionBeginning($this));
            case 'committed':
                return $this->events->dispatch(new TransactionCommitted($this));
            case 'rollingBack':
                return $this->events->dispatch(new TransactionRolledBack($this));
        }
    }

    /**
     * Fire the given event if possible.
     *
     * @param mixed $event
     * @return void
     */
    protected function event($event): void
    {
        if (isset($this->events)) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Get a new raw query expression.
     *
     * @param mixed $value
     * @return Expression
     */
    public function raw($value): Expression
    {
        return new Expression($value);
    }

    /**
     * Indicate if any records have been modified.
     *
     * @param bool $value
     * @return void
     */
    public function recordsHaveBeenModified($value = true): void
    {
        if (!$this->recordsModified) {
            $this->recordsModified = $value;
        }
    }

    /**
     * Is Doctrine available?
     *
     * @return bool
     */
    public function isDoctrineAvailable(): bool
    {
        return class_exists('Doctrine\DBAL\Connection');
    }

    /**
     * Get a Doctrine Schema Column instance.
     *
     * @param string $table
     * @param string $column
     * @return Column
     */
    public function getDoctrineColumn($table, $column): Column
    {
        $schema = $this->getDoctrineSchemaManager();

        return $schema->listTableDetails($table)->getColumn($column);
    }

    /**
     * Get the Doctrine DBAL schema manager for the connection.
     *
     * @return AbstractSchemaManager
     */
    public function getDoctrineSchemaManager(): AbstractSchemaManager
    {
        return $this->getDoctrineDriver()->getSchemaManager($this->getDoctrineConnection());
    }

    /**
     * Get the Doctrine DBAL database connection instance.
     *
     * @return DoctrineConnection
     */
    public function getDoctrineConnection(): DoctrineConnection
    {
        if (is_null($this->doctrineConnection)) {
            $driver = $this->getDoctrineDriver();

            $this->doctrineConnection = new DoctrineConnection(array_filter([
                'pdo' => $this->getPdo(),
                'dbname' => $this->getDatabaseName(),
                'driver' => $driver->getName(),
                'serverVersion' => $this->getConfig('server_version'),
            ]), $driver);
        }

        return $this->doctrineConnection;
    }

    /**
     * Get the current PDO connection.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection parameter without executing any reconnect logic.
     *
     * @return PDO|Closure|null
     */
    public function getRawPdo()
    {
        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
     *
     * @return PDO
     */
    public function getReadPdo(): PDO
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if ($this->recordsModified && $this->getConfig('sticky')) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Get the current read PDO connection parameter without executing any reconnect logic.
     *
     * @return PDO|Closure|null
     */
    public function getRawReadPdo()
    {
        return $this->readPdo;
    }

    /**
     * Set the PDO connection.
     *
     * @param PDO|Closure|null $pdo
     * @return $this
     */
    public function setPdo($pdo): self
    {
        $this->transactions = 0;

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Set the PDO connection used for reading.
     *
     * @param PDO|Closure|null $pdo
     * @return $this
     */
    public function setReadPdo($pdo): self
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * @param callable $reconnector
     * @return $this
     */
    public function setReconnector(callable $reconnector): self
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getConfig('name');
    }

    /**
     * Get an option from the configuration options.
     *
     * @param string|null $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->getConfig('driver');
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return QueryGrammar
     */
    public function getQueryGrammar(): QueryGrammar
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param QueryGrammar $grammar
     * @return $this
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar): self
    {
        $this->queryGrammar = $grammar;

        return $this;
    }

    /**
     * Get the schema grammar used by the connection.
     *
     * @return \Mini\Database\Mysql\Schema\Grammars\Grammar
     */
    public function getSchemaGrammar(): Schema\Grammars\Grammar
    {
        return $this->schemaGrammar;
    }

    /**
     * Set the schema grammar used by the connection.
     *
     * @param \Mini\Database\Mysql\Schema\Grammars\Grammar $grammar
     * @return $this
     */
    public function setSchemaGrammar(Schema\Grammars\Grammar $grammar): self
    {
        $this->schemaGrammar = $grammar;

        return $this;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return Processor
     */
    public function getPostProcessor(): Processor
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param Processor $processor
     * @return $this
     */
    public function setPostProcessor(Processor $processor): self
    {
        $this->postProcessor = $processor;

        return $this;
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return Dispatcher
     */
    public function getEventDispatcher(): Dispatcher
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param Dispatcher $events
     * @return $this
     */
    public function setEventDispatcher(Dispatcher $events): self
    {
        $this->events = $events;

        return $this;
    }

    /**
     * Unset the event dispatcher for this connection.
     *
     * @return void
     */
    public function unsetEventDispatcher(): void
    {
        $this->events = null;
    }

    /**
     * Determine if the connection is in a "dry run".
     *
     * @return bool
     */
    public function pretending(): bool
    {
        return $this->pretending === true;
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging(): bool
    {
        return $this->loggingQueries;
    }

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * @param string $database
     * @return $this
     */
    public function setDatabaseName($database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * @param string $prefix
     * @return $this
     */
    public function setTablePrefix($prefix): self
    {
        $this->tablePrefix = $prefix;

        $this->getQueryGrammar()->setTablePrefix($prefix);

        return $this;
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * @param Grammar $grammar
     * @return Grammar
     */
    public function withTablePrefix(Grammar $grammar): Grammar
    {
        $grammar->setTablePrefix($this->tablePrefix);

        return $grammar;
    }

    /**
     * Register a connection resolver.
     *
     * @param string $driver
     * @param Closure $callback
     * @return void
     */
    public static function resolverFor($driver, Closure $callback): void
    {
        static::$resolvers[$driver] = $callback;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * @param string $driver
     * @return mixed
     */
    public static function getResolver($driver)
    {
        return static::$resolvers[$driver] ?? null;
    }
}
