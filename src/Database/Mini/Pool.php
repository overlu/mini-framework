<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Support\Coroutine;
use PDO;
use Mini\Singleton;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;

class Pool
{
    use Singleton;

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

    private function __construct(array $config = [])
    {
        if (empty($this->pools)) {
            $this->initialize($config);
        }
    }

    private function initialize(array $config)
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
        $conf = array_replace_recursive($this->config, config('database.' . ($key ?: 'default'), []));
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
        $key = $key ?: 'default';
        if (Coroutine::inCoroutine()) {
            if (empty($this->pools)) {
                $this->initialize(config('database', []));
            }
            $connection = $this->pools[$key]->get();
            Coroutine::defer(function () use ($key, $connection) {
                $this->close($key, $connection);
            });
            return $connection;
        }
        return new PDOProxy(function () use ($key) {
            return $this->getPdo($key);
        });
    }

    /**
     * @param string $key
     * @param null $connection
     */
    public function close(string $key = '', $connection = null): void
    {
        $this->pools[$key ?: 'default']->put($connection);
    }


}
