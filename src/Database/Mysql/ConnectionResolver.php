<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

class ConnectionResolver implements ConnectionResolverInterface
{
    /**
     * All of the registered connections.
     *
     * @var array
     */
    protected array $connections = [];

    /**
     * The default connection name.
     *
     * @var string
     */
    protected string $default;

    /**
     * Create a new connection resolver instance.
     *
     * @param array $connections
     * @return void
     */
    public function __construct(array $connections = [])
    {
        foreach ($connections as $name => $connection) {
            $this->addConnection($name, $connection);
        }
    }

    /**
     * Get a database connection instance.
     *
     * @param string|null $name
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\ConnectionInterface
=======
     * @return ConnectionInterface
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function connection(?string $name = null): ConnectionInterface
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        return $this->connections[$name];
    }

    /**
     * Add a connection to the resolver.
     *
     * @param string $name
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\ConnectionInterface $connection
=======
     * @param ConnectionInterface $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return void
     */
    public function addConnection(string $name, ConnectionInterface $connection): void
    {
        $this->connections[$name] = $connection;
    }

    /**
     * Check if a connection has been registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->default;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection(string $name): void
    {
        $this->default = $name;
    }
}
