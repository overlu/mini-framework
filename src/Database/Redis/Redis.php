<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Singleton;

class Redis
{
    use Singleton;

    protected \Redis $connection;

    public function __construct(string $connection = '', array $config = [])
    {
        $this->connection = Pool::getInstance($config)->getConnection($connection);
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        return self::getInstance()->connection->{$name}(...$arguments);
    }
}
