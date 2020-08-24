<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Singleton;
use Mini\Support\Coroutine;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

class Pool
{
    use Singleton;

    protected array $pools = [];

    protected array $config = [
        'host' => 'localhost',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'time_out' => 1,
        'size' => 64,
    ];

    private function __construct(array $config = [])
    {
        if (empty($this->pools)) {
            foreach ($config as $key => $value) {
                $conf = array_replace_recursive($this->config, $value);
                $this->pools[$key] = new RedisPool(
                    (new RedisConfig())
                        ->withHost($conf['host'])
                        ->withPort((int)$conf['port'])
                        ->withAuth((string)$conf['password'])
                        ->withDbIndex((int)$conf['database'])
                        ->withTimeout((float)$conf['time_out']),
                    (int)$conf['size']
                );
            }
        }
    }

    public function getConnection($key = ''): \Redis
    {
        $key = $key ?: 'default';
        if (Coroutine::inCoroutine()) {
            $connection = $this->pools[$key]->get();
            Coroutine::defer(function () use ($key, $connection) {
                $this->close($key, $connection);
            });
            return $connection;
        }
        return $this->getRedis($key);
    }

    public function close($key = '', $connection = null): void
    {
        $this->pools[$key ?: 'default']->put($connection);
    }

    public function getRedis($key = ''): \Redis
    {
        $conf = array_replace_recursive($this->config, config('database.' . ($key ?: 'default'), []));
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
}
