<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Coroutine;

use Mini\Support\Coroutine;
use Mini\Support\Traits\Container;
use Swoole\Coroutine as SwooleCoroutine;

class Locker
{
    use Container;

    /**
     *
     * @param $key
     * @param $id
     */
    public static function add($key, $id): void
    {
        self::$container[$key][] = $id;
    }

    public static function clear($key): void
    {
        unset(self::$container[$key]);
    }

    public static function lock($key): bool
    {
        if (! self::has($key)) {
            self::add($key, 0);
            return true;
        }
        self::add($key, Coroutine::id());
        SwooleCoroutine::suspend();
        return false;
    }

    public static function unlock($key): void
    {
        if (self::has($key)) {
            $ids = self::get($key);
            foreach ($ids as $id) {
                if ($id > 0) {
                    SwooleCoroutine::resume($id);
                }
            }
            self::clear($key);
        }
    }
}
