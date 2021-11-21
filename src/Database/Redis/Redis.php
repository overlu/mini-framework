<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Exception;
use Mini\Support\Coroutine;
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
     * @return RedisPool
     * @throws Exception
     */
    private function connection(string $key = 'default'): RedisPool
    {
        if (empty($this->pools[$key])) {
            $conf = $this->config[$key];
            if (empty($conf)) {
                throw new Exception('redis connection [' . $key . '] not exists');
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
        return $this->pools[$key];
    }

    /**
     * @param string $key
     * @return \Redis
     * @throws Exception
     */
    public function getConnection(string $key = 'default'): \Redis
    {
        if (Coroutine::inCoroutine()) {
            $connection = $this->connection($key)->get();
            Coroutine::defer(function () use ($key, $connection) {
                $this->close($key, $connection);
            });
            return $connection;
        }
        return $this->getRedis($key);
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
     * @throws Exception
     */
    public function getRedis(string $key = 'default'): \Redis
    {
        $conf = $this->config[$key];
        if (empty($conf)) {
            throw new Exception('redis connection [' . $key . '] not exists');
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
