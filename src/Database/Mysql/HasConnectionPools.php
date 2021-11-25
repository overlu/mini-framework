<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

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
    protected array $poolsConfig;

    /**
     * The redis pools.
     *
     * @var ConnectionPool[]
     */
    protected static array $pools = [];

    /**
     * Get the connection from pool.
     *
     * @param string $name
     * @return mixed
     */
    protected function getConnectionFromPool(string $name)
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
    protected function initializePool(string $name): void
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
    protected function getConnectionFromContext(string $name)
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
    protected function setConnectionToContext(string $name, $connection): void
    {
        $key = $this->getConnectionKeyInContext($name);
        Context::set($key, $connection);
    }

    /**
     * Get the connection key in coroutine context.
     *
     * @param string $name
     * @return string
     */
    protected function getConnectionKeyInContext(string $name): string
    {
        return $name;
    }

    /**
     * Determine if the connection is a pool connection.
     *
     * @param string $name
     * @return bool
     */
    protected function isPoolConnection(string $name): bool
    {
        return Coroutine::getCid() > 0 && isset($this->poolsConfig[$name]);
    }

    /**
     * 关闭连接池
     * @return array|int[]|string[]
     */
    public function closePool(): array
    {
        if (empty(static::$pools)) {
            return [];
        }
        foreach (static::$pools as $pool) {
            $pool->close();
        }
        $keys = array_keys(static::$pools);
        static::$pools = [];
        return $keys;
    }
}