<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

use Mini\Context;

trait StaticInstance
{
    public static function getInstance($params = [], $refresh = false)
    {
        $key = static::class;
        $instance = null;
        if (Context::has($key)) {
            $instance = Context::get($key);
        }

        if ($refresh || !$instance instanceof static) {
            $instance = new static(...$params);
            Context::set($key, $instance);
        }

        return $instance;
    }
}
