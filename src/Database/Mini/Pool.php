<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Context;
use Mini\Support\Coroutine;
use PDO;
use Mini\Singleton;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;

class Pool
{
    /**
     * @var PDOPool[]
     */
    protected array $pools = [];

    protected array $config = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
        'options' => [],
        'size' => 64,
    ];
    /**
     * @var mixed|null
     */
    protected string $defaultConnection;

    public function __construct(array $config = [])
    {
        /*if (empty($this->pools)) {
            $this->initialize($config);
        }*/
        $this->defaultConnection = config('database.default', 'mysql');
    }

    /**
     * @param array $config
     */
    private function initialize(array $config): void
    {
        foreach ($config as $key => $value) {
            $conf = array_replace_recursive($this->config, $value);
            $this->pools[$key] = new PDOPool(
                (new PDOConfig())
                    ->withDriver($conf['driver'])
                    ->withHost($conf['host'])
                    ->withPort($conf['port'])
                    ->withDbName($conf['database'])
                    ->withCharset($conf['charset'])
                    ->withUsername($conf['username'])
                    ->withPassword($conf['password'])
                    ->withOptions($conf['options']),
                $conf['size']
            );
        }
    }

    /**
     * @param string $key
     * @return PDO
     */
    public function getPdo(string $key = ''): PDO
    {
        $conf = array_replace_recursive($this->config, config('database.connections.' . ($key ?: $this->defaultConnection), []));
        return new PDO(
            "{$conf['driver']}:"
            . "host={$conf['host']};" . "port={$conf['port']};"
            . "dbname={$conf['database']};"
            . "charset={$conf['charset']}",
            $conf['username'],
            $conf['password'],
            $conf['options']
        );
    }

    /**
     * @param string $key
     * @return PDOProxy
     */
    public function getConnection(string $key = ''): PDOProxy
    {
        $key = $key ?: $this->defaultConnection;
        if (!Coroutine::inCoroutine()) {
            return new PDOProxy(function () use ($key) {
                return $this->getPdo($key);
            });
        }
        if ($connection = $this->getConnectionFromContext($key)) {
            return $connection;
        }
        return $this->getConnectionFromPool($key);
    }

    protected function getConnectionFromPool($key)
    {
        if (empty($this->pools)) {
            $this->initialize(config('database.connections', []));
        }
        $connection = $this->pools[$key]->get();
        $this->setConnectionToContext($key, $connection);
        Coroutine::defer(function () use ($key, $connection) {
            $this->close($key, $connection);
        });
        return $connection;
    }

    /**
     * @param string $key
     * @param null $connection
     */
    public function close(string $key = '', $connection = null): void
    {
        $this->pools[$key ?: $this->defaultConnection]->put($connection);
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
    }

    /**
     * Get the connection key in coroutine context.
     *
     * @param string $name
     * @return string
     */
    protected function getConnectionKeyInContext(string $name): string
    {
        return 'mini-db.connections.' . $name;
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
