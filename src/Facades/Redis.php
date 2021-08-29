<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Contracts\Container\BindingResolutionException;

/**
 * Class Redis
 * @package Mini\Facades
 */
class Redis extends Facade
{
    /**
     * @throws BindingResolutionException
     */
    protected static function getFacadeAccessor()
    {
        return app('redis')->getConnection('default');
    }

    /**
     * @param string $connection
     * @return \Redis
     * @throws BindingResolutionException
     */
    public static function connection(string $connection = 'default'): \Redis
    {
        return app('redis')->getConnection($connection);
    }
}