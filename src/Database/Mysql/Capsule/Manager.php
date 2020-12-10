<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Capsule;

use Mini\Container\Container;
use Mini\Contracts\Events\Dispatcher;
use Mini\Database\Mysql\Connectors\ConnectionFactory;
use Mini\Database\Mysql\DatabaseManager;
use Mini\Database\Mysql\Eloquent\Model as Eloquent;
use Mini\Support\Traits\CapsuleManagerTrait;
use Mini\Config;
use PDO;

class Manager
{
    use CapsuleManagerTrait;

    /**
     * The database manager instance.
     *
     * @var DatabaseManager
     */
    protected DatabaseManager $manager;

    protected array $pool_config = [];

    /**
     * Create a new database capsule manager.
     *
     * @param \Mini\Container\Container|null $container
     * @return void
     */
    public function __construct(Container $container = null)
    {
        $config = Config::getInstance()->get('database', []);
        if (!empty($config)) {
            $this->pool_config = $config;
        }
        $this->setupContainer($container ?: new Container);

        // Once we have the container setup, we will setup the default configuration
        // options in the container "config" binding. This will make the database
        // manager work correctly out of the box without extreme configuration.
        $this->setupDefaultConfiguration();

        $this->setupManager();
    }

    /**
     * Setup the default database configuration options.
     *
     * @return void
     */
    protected function setupDefaultConfiguration(): void
    {
        $this->container['config']['database.fetch'] = PDO::FETCH_OBJ;

        $this->container['config']['database.default'] = config('database.default', 'mysql');
    }

    /**
     * Build the database manager instance.
     *
     * @return void
     */
    protected function setupManager(): void
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory, $this->pool_config);
    }

    /**
     * Get a connection instance from the global manager.
     *
     * @param string|null $connection
     * @return \Mini\Database\Mysql\Connection
     */
    public static function connection($connection = null): \Mini\Database\Mysql\Connection
    {
        return static::$instance->getConnection($connection);
    }

    /**
     * Get a fluent query builder instance.
     *
     * @param \Closure|\Mini\Database\Mysql\Query\Builder|string $table
     * @param string|null $as
     * @param string|null $connection
     * @return \Mini\Database\Mysql\Query\Builder
     */
    public static function table($table, $as = null, $connection = null): \Mini\Database\Mysql\Query\Builder
    {
        return static::$instance->connection($connection)->table($table, $as);
    }

    /**
     * Get a schema builder instance.
     *
     * @param string|null $connection
     * @return \Mini\Database\Mysql\Schema\Builder
     */
    public static function schema($connection = null): \Mini\Database\Mysql\Schema\Builder
    {
        return static::$instance->connection($connection)->getSchemaBuilder();
    }

    /**
     * Get a registered connection instance.
     *
     * @param string|null $name
     * @return \Mini\Database\Mysql\Connection
     */
    public function getConnection($name = null): \Mini\Database\Mysql\Connection
    {
        return $this->manager->connection($name);
    }

    /**
     * Register a connection with the manager.
     *
     * @param array $config
     * @param string $name
     * @return void
     */
    public function addConnection(array $config, $name = 'default'): void
    {
        $connections = $this->container['config']['database.connections'];

        $connections[$name] = $config;

        $this->container['config']['database.connections'] = $connections;
    }

    /**
     * Bootstrap Eloquent so it is ready for usage.
     *
     * @return void
     */
    public function bootEloquent(): void
    {
        Eloquent::setConnectionResolver($this->manager);

        // If we have an event dispatcher instance, we will go ahead and register it
        // with the Eloquent ORM, allowing for model callbacks while creating and
        // updating "model" instances; however, it is not necessary to operate.
        if ($dispatcher = $this->getEventDispatcher()) {
            Eloquent::setEventDispatcher($dispatcher);
        }
    }

    /**
     * Set the fetch mode for the database connections.
     *
     * @param int $fetchMode
     * @return $this
     */
    public function setFetchMode($fetchMode): self
    {
        $this->container['config']['database.fetch'] = $fetchMode;

        return $this;
    }

    /**
     * Get the database manager instance.
     *
     * @return DatabaseManager
     */
    public function getDatabaseManager(): DatabaseManager
    {
        return $this->manager;
    }

    public static function getStaticDatabaseManager(): DatabaseManager
    {
        return static::$instance->manager;
    }

    /**
     * Get the current event dispatcher instance.
     *
     * @return \Mini\Contracts\Events\Dispatcher|null
     */
    public function getEventDispatcher(): ?Dispatcher
    {
        if ($this->container->bound('events')) {
            return $this->container['events'];
        }
        return null;
    }

    /**
     * Set the event dispatcher instance to be used by connections.
     *
     * @param \Mini\Contracts\Events\Dispatcher $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher): void
    {
        $this->container->instance('events', $dispatcher);
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::connection()->$method(...$parameters);
    }
}
