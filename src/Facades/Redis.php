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
        return app('redis')->getConnection('default');
    }

    public static function connection(string $connection = 'default')
    {
        return app('redis')->getConnection($connection);
    }
}