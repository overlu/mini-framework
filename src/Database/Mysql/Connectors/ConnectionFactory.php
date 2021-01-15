<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Connectors;

use Closure;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Container\Container;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\MySqlConnection;
use Mini\Database\Mysql\PostgresConnection;
use Mini\Database\Mysql\SQLiteConnection;
use Mini\Database\Mysql\SqlServerConnection;
use Mini\Support\Arr;
use InvalidArgumentException;
use PDO;
use PDOException;

class ConnectionFactory
{
    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Create a new connection factory instance.
     *
<<<<<<< HEAD
     * @param \Mini\Contracts\Container\Container $container
=======
     * @param Container $container
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param array $config
     * @param string|null $name
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Connection
=======
     * @return Connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function make(array $config, ?string $name = null): Connection
    {
        $config = $this->parseConfig($config, $name);

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }

        return $this->createSingleConnection($config);
    }

    /**
     * Parse and prepare the database configuration.
     *
     * @param array $config
     * @param string $name
     * @return array
     */
    protected function parseConfig(array $config, string $name): array
    {
        return Arr::add(Arr::add($config, 'prefix', ''), 'name', $name);
    }

    /**
     * Create a single database connection instance.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Connection
=======
     * @return Connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function createSingleConnection(array $config): Connection
    {
        $pdo = $this->createPdoResolver($config);

        return $this->createConnection(
            $config['driver'], $pdo, $config['database'], $config['prefix'], $config
        );
    }

    /**
     * Create a read / write database connection instance.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Connection
=======
     * @return Connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function createReadWriteConnection(array $config): Connection
    {
        $connection = $this->createSingleConnection($this->getWriteConfig($config));

        return $connection->setReadPdo($this->createReadPdo($config));
    }

    /**
     * Create a new PDO instance for reading.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Closure
=======
     * @return Closure
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function createReadPdo(array $config): callable
    {
        return $this->createPdoResolver($this->getReadConfig($config));
    }

    /**
     * Get the read configuration for a read / write connection.
     *
     * @param array $config
     * @return array
     */
    protected function getReadConfig(array $config): array
    {
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'read')
        );
    }

    /**
     * Get the write configuration for a read / write connection.
     *
     * @param array $config
     * @return array
     */
    protected function getWriteConfig(array $config): array
    {
        return $this->mergeReadWriteConfig(
            $config, $this->getReadWriteConfig($config, 'write')
        );
    }

    /**
     * Get a read / write level configuration.
     *
     * @param array $config
     * @param string $type
     * @return array
     */
    protected function getReadWriteConfig(array $config, string $type): array
    {
        return isset($config[$type][0])
            ? Arr::random($config[$type])
            : $config[$type];
    }

    /**
     * Merge a configuration for a read / write connection.
     *
     * @param array $config
     * @param array $merge
     * @return array
     */
    protected function mergeReadWriteConfig(array $config, array $merge): array
    {
        return Arr::except(array_merge($config, $merge), ['read', 'write']);
    }

    /**
     * Create a new Closure that resolves to a PDO instance.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Closure
=======
     * @return Closure
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function createPdoResolver(array $config): callable
    {
        return array_key_exists('host', $config)
            ? $this->createPdoResolverWithHosts($config)
            : $this->createPdoResolverWithoutHosts($config);
    }

    /**
     * Create a new Closure that resolves to a PDO instance with a specific host or an array of hosts.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Closure
=======
     * @return Closure
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     *
     * @throws PDOException
     */
    protected function createPdoResolverWithHosts(array $config): callable
    {
        return function () use ($config) {
            foreach (Arr::shuffle($hosts = $this->parseHosts($config)) as $key => $host) {
                $config['host'] = $host;

                try {
                    return $this->createConnector($config)->connect($config);
                } catch (PDOException $e) {
                    continue;
                }
            }

            throw $e;
        };
    }

    /**
     * Parse the hosts configuration item into an array.
     *
     * @param array $config
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function parseHosts(array $config): array
    {
        $hosts = Arr::wrap($config['host']);

        if (empty($hosts)) {
            throw new InvalidArgumentException('Database hosts array is empty.');
        }

        return $hosts;
    }

    /**
     * Create a new Closure that resolves to a PDO instance where there is no configured host.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Closure
=======
     * @return Closure
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    protected function createPdoResolverWithoutHosts(array $config): callable
    {
        return function () use ($config) {
            return $this->createConnector($config)->connect($config);
        };
    }

    /**
     * Create a connector instance based on the configuration.
     *
     * @param array $config
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\Connectors\ConnectorInterface
=======
     * @return ConnectorInterface
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    public function createConnector(array $config): ConnectorInterface
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector;
            case 'pgsql':
                return new PostgresConnector;
            case 'sqlite':
                return new SQLiteConnector;
            case 'sqlsrv':
                return new SqlServerConnector;
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}].");
    }

    /**
     * Create a new connection instance.
     *
     * @param string $driver
<<<<<<< HEAD
     * @param \PDO|\Closure $connection
     * @param string $database
     * @param string $prefix
     * @param array $config
     * @return \Mini\Database\Mysql\Connection
=======
     * @param PDO|Closure $connection
     * @param string $database
     * @param string $prefix
     * @param array $config
     * @return Connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     *
     * @throws InvalidArgumentException
     */
    protected function createConnection(string $driver, $connection, string $database, string $prefix = '', array $config = []): Connection
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
                return new PostgresConnection($connection, $database, $prefix, $config);
            case 'sqlite':
                return new SQLiteConnection($connection, $database, $prefix, $config);
            case 'sqlsrv':
                return new SqlServerConnection($connection, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}].");
    }
}
