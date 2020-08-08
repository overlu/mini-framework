<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
namespace Mini\Database\Mysql;

use Mini\Context;
use RuntimeException;
use Swoole\ConnectionPool;
use Swoole\Coroutine;

trait HasConnectionPools
{
    /**
     * The pools configuration.
     *
     * @var array
     */
    protected $poolsConfig;

    /**
     * The redis pools.
     *
     * @var array
     */
    protected static $pools = [];

    /**
     * Get the connection from pool.
     *
     * @param string $name
     * @return mixed
     */
    protected function getConnectionFromPool($name)
    {
        if (!isset(static::$pools[$name])) {
            $this->initializePool($name);
        }

        $pool = static::$pools[$name];

        $connection = $pool->get();

        $this->setConnectionToContext($name, $connection);

        Coroutine::defer(static function () use ($pool, $connection) {
            $pool->put($connection);
        });

        return $connection;
    }

    /**
     * Initialize the connection pool.
     *
     * @param string $name
     * @return void
     */
    protected function initializePool($name): void
    {
        $capacity = (int)($this->poolsConfig[$name]['size'] ?? 64);
        $pool = new ConnectionPool(function () use ($name) {
            return $this->resolveConnection($name);
        }, $capacity);

        static::$pools[$name] = $pool;
    }

    /**
     * Resolve the connection.
     *
     * @param string $name
     * @return mixed
     */
    protected function resolveConnection(string $name)
    {
        throw new RuntimeException('You need to implement resolveConnection method.');
    }

    /**
     * Get the connection from coroutine context.
     *
     * @param string $name
     * @return mixed
     */
    protected function getConnectionFromContext($name)
    {
        $key = $this->getConnectionKeyInContext($name);
        return Context::get($key);
    }

    /**
     * Set the connection to coroutine context.
     *
     * @param string $name
     * @param mixed $connection
     * @return void
     */
    protected function setConnectionToContext($name, $connection): void
    {
        $key = $this->getConnectionKeyInContext($name);
        Context::set($key, $connection);
//        Coroutine::getContext()[$key] = $connection;
    }

    /**
     * Get the connection key in coroutine context.
     *
     * @param string $name
     * @return string
     */
    protected function getConnectionKeyInContext($name): string
    {
        return $name;
    }

    /**
     * Determine if the connection is a pool connection.
     *
     * @param string $name
     * @return bool
     */
    protected function isPoolConnection($name): bool
    {
        return Coroutine::getCid() > 0 && isset($this->poolsConfig[$name]);
    }
}