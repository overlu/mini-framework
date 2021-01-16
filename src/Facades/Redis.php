<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class Redis
 * @package Mini\Facades
 */
class Redis extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Mini\Database\Redis\Pool::getInstance()->getConnection();
    }

    public static function connection(string $connection = 'default')
    {
        return \Mini\Database\Redis\Pool::getInstance()->getConnection($connection);
    }
}