<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Context;
use Mini\Support\Coroutine;
use RuntimeException;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

class Redis
{
    /**
     * @var RedisPool[]
     */
    protected array $pools = [];
    protected array $config = [];

    public function __construct()
    {
        $this->config = config('redis', []);

    }

    /**
     * @param string $key
     */
    private function initialize(string $key): void
    {
        $conf = $this->config[$key];
        if (empty($conf)) {
            throw new RuntimeException('redis connection [' . $key . '] not exists');
        }
        $this->pools[$key] = new RedisPool(
            (new RedisConfig())
                ->withHost($conf['host'])
                ->withPort((int)$conf['port'])
                ->withAuth((string)$conf['password'])
                ->withDbIndex((int)$conf['database'])
                ->withTimeout((float)$conf['time_out']),
            (int)($conf['size'] ?? 64)
        );
    }

    /**
     * @param string $key
     * @return \Redis
     */
    public function getConnection(string $key = 'default'): \Redis
    {
        if (Coroutine::inCoroutine()) {
            if ($connection = $this->getConnectionFromContext($key)) {
                return $connection;
            }
            return $this->getConnectionFromPool($key);
        }
        return $this->getRedis($key);
    }

    /**
     * @param string $key
     * @return \Redis
     */
    protected function getConnectionFromPool(string $key): \Redis
    {
        if (empty($this->pools[$key])) {
            $this->initialize($key);
        }
        $connection = $this->pools[$key]->get();
        $this->setConnectionToContext($key, $connection);
        Coroutine::defer(function () use ($key, $connection) {
            $this->close($key, $connection);
        });
        return $connection;
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
     * @param string $key
     * @param null $connection
     */
    public function close(string $key = 'default', $connection = null): void
    {
        if (!empty($this->pools[$key])) {
            $this->pools[$key]->put($connection);
        }
    }

    /**
     * @param string $key
     * @return \Redis
     */
    public function getRedis(string $key = 'default'): \Redis
    {
        $conf = $this->config[$key];
        if (empty($conf)) {
            throw new RuntimeException('redis connection [' . $key . '] not exists');
        }
        $redis = new \Redis();
        /* Compatible with different versions of Redis extension as much as possible */
        $arguments = [
            $conf['host'],
            (int)$conf['port'],
            (float)$conf['time_out'],
        ];
        $redis->connect(...$arguments);
        if ($conf['password']) {
            $redis->auth($conf['password']);
        }
        if ($conf['database'] !== 0) {
            $redis->select((int)$conf['database']);
        }
        return $redis;
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
     * Get the connection key in coroutine context.
     *
     * @param string $name
     * @return string
     */
    protected function getConnectionKeyInContext(string $name): string
    {
        return 'redis.connections.' . $name;
    }

    /**
     * 关闭连接池
     * @return array|int[]|string[]
     */
    public function closePool(): array
    {
        if (empty($this->pools)) {
            return [];
        }
        foreach ($this->pools as $pool) {
            $pool->close();
        }
        $keys = array_keys($this->pools);
        $this->pools = [];
        return $keys;
    }
}
