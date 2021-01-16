<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Support\Dotenv;

/**
 * Class Env
 * @method static array all()
 * @method static bool has($key)
 * @method static mixed get($key, $default = null)
 * @method static void set($key, $value = null)
 * @method static void setMany($array)
 * @package Mini\Facades
 */
class Env extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Dotenv::getInstance();
    }
}