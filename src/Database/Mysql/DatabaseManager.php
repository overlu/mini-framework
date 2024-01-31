<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Contracts\Container\Container;
use Mini\Contracts\Foundation\Application;
use Mini\Database\Mysql\Connectors\ConnectionFactory;
use Mini\Support\Arr;
use Mini\Support\ConfigurationUrlParser;
use Mini\Support\Str;
use InvalidArgumentException;
use PDO;

/**
 * @mixin Connection
 */
class DatabaseManager implements ConnectionResolverInterface
{
    use HasConnectionPools;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The database connection factory instance.
     *
     * @var ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected array $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected array $extensions = [];

    /**
     * The callback to be executed to reconnect to a database.
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * The callback to be executed to reconnect to a database in pool.
     *
     * @var callable
     */
    protected $poolReconnector;

    /**
     * Create a new database manager instance.
     *
     * @param Container $app
     * @param ConnectionFactory $factory
     * @param array $poolsConfig
     */
    public function __construct($app, ConnectionFactory $factory, array $poolsConfig = [])
    {
        $this->app = $app;
        $this->factory = $factory;

        $this->reconnector = function ($connection) {
            $this->reconnect($connection->getName());
        };

        $this->poolsConfig = $poolsConfig;

        $this->poolReconnector = function ($connection) {
            $this->poolReconnect($connection);
        };
    }

    /**
     * Get a database connection instance.
     *
     * @param string|null $name
     * @return Connection
     */
    public function connection($name = null): ConnectionInterface
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!$this->isPoolConnection($name)) {
            [$database, $type] = $this->parseConnectionName($name);

            $name = $name ?: $database;

            // If we haven't created this connection, we'll create it based on the config
            // provided in the application. Once we've created the connections we will
            // set the "fetch mode" for PDO which determines the query return types.
            if (!isset($this->connections[$name])) {
                $this->connections[$name] = $this->configure(
                    $this->makeConnection($database), $type
                );
            }

            return $this->connections[$name];
        }

        if ($connection = $this->getConnectionFromContext($name)) {
            return $connection;
        }
        return $this->getConnectionFromPool($name);

    }

    /**
     * Resolve the connection.
     *
     * @param string $name
     * @return Connection
     */
    protected function resolveConnection(string $name): Connection
    {
        [$database, $type] = $this->parseConnectionName($name);

        $connection = $this->configure($this->makeConnection($database), $type);

        $connection->setReconnector($this->poolReconnector);

        return $connection;
    }

    /**
     * Get the connection key in coroutine context.
     *
     * @param string $name
     * @return string
     */
    protected function getConnectionKeyInContext(string $name): string
    {
        return 'db.connections.' . $name;
    }

    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param string $name
     * @return array
     */
    protected function parseConnectionName(string $name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        return Str::endsWith($name, ['::read', '::write'])
            ? explode('::', $name, 2) : [$name, null];
    }

    /**
     * Make the database connection instance.
     *
     * @param string $name
     * @return Connection
     */
    protected function makeConnection(string $name): Connection
    {
        $config = $this->configuration($name);

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        // Next we will check to see if an extension has been registered for a driver
        // and will call the Closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.
        if (isset($this->extensions[$driver = $config['driver']])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name);
    }

    /**
     * Get the configuration for a connection.
     *
     * @param string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration(string $name): array
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the database connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = config('database.connections');

        if (is_null($config = Arr::get($connections, $name))) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return (new ConfigurationUrlParser)
            ->parseConfiguration($config);
    }

    /**
     * Prepare the database connection instance.
     *
     * @param Connection $connection
     * @param string|null $type
     * @return Connection
     */
    protected function configure(Connection $connection, string $type = null): Connection
    {
        $connection = $this->setPdoForType($connection, $type);

        // First we'll set the fetch mode and a few other dependencies of the database
        // connection. This method basically just configures and prepares it to get
        // used by the application. Once we're finished we'll return it back out.
        if ($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        // Here we'll set a reconnector callback. This reconnector can be any callable
        // so we will set a Closure to reconnect from this manager with the name of
        // the connection, which will allow us to reconnect from the connections.
        $connection->setReconnector($this->reconnector);

        return $connection;
    }

    /**
     * Prepare the read / write mode for database connection instance.
     *
     * @param Connection $connection
     * @param string|null $type
     * @return Connection
     */
    protected function setPdoForType(Connection $connection, string $type = null): Connection
    {
        if ($type === 'read') {
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type === 'write') {
            $connection->setReadPdo($connection->getPdo());
        }

        return $connection;
    }

    /**
     * Disconnect from the given database and remove from local cache.
     *
     * @param string|null $name
     * @return void
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * @param string|null $name
     * @return void
     */
    public function disconnect(?string $name = null): void
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
     *
     * @param string|null $name
     * @return Connection|null
     */
    public function reconnect(?string $name = null): ?Connection
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!$this->isPoolConnection($name)) {
            $this->disconnect($name);

            if (!isset($this->connections[$name])) {
                return $this->connection($name);
            }

            return $this->refreshPdoConnections($name);
        }
        return null;

    }

    /**
     * Reconnect the connection in pool.
     *
     * @param Connection $connection
     * @return Connection
     */
    public function poolReconnect(Connection $connection): Connection
    {
        $connection->disconnect();

        $fresh = $this->makeConnection($connection->getName());

        return $connection
            ->setPdo($fresh->getRawPdo())
            ->setReadPdo($fresh->getRawReadPdo());
    }

    /**
     * Set the default database connection for the callback execution.
     *
     * @param string $name
     * @param callable $callback
     * @return mixed
     */
    public function usingConnection(string $name, callable $callback): mixed
    {
        $previousName = $this->getDefaultConnection();

        $this->setDefaultConnection($name);

        return tap($callback(), function () use ($previousName) {
            $this->setDefaultConnection($previousName);
        });
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param string $name
     * @return Connection
     */
    protected function refreshPdoConnections(string $name): Connection
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]
            ->setPdo($fresh->getRawPdo())
            ->setReadPdo($fresh->getRawReadPdo());
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return config('database.default');
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection(string $name): void
    {
        $this->app['config']['database.default'] = $name;
    }

    /**
     * Get all of the support drivers.
     *
     * @return array
     */
    public function supportedDrivers(): array
    {
        return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    }

    /**
     * Get all of the drivers that are actually available.
     *
     * @return array
     */
    public function availableDrivers(): array
    {
        return array_intersect(
            $this->supportedDrivers(),
            str_replace('dblib', 'sqlsrv', PDO::getAvailableDrivers())
        );
    }

    /**
     * Register an extension connection resolver.
     *
     * @param string $name
     * @param callable $resolver
     * @return void
     */
    public function extend(string $name, callable $resolver): void
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Set the database reconnector callback.
     *
     * @param callable $reconnector
     * @return void
     */
    public function setReconnector(callable $reconnector): void
    {
        $this->reconnector = $reconnector;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
