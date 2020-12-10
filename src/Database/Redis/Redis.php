<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

/**
 * Class Redis
 * @package Mini\Database\Redis
 */
class Redis
{
    public static function connection($connection = 'default'): \Redis
    {
        return Pool::getInstance()->getConnection($connection);
    }

    public static function __callStatic($name, $arguments)
    {
        return Pool::getInstance()->getConnection('default')->{$name}(...$arguments);
    }
}
