<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

trait Singleton
{
    private static self $instance;

    /**
     * @param mixed ...$args
     * @return Singleton|static
     */
    public static function getInstance(...$args): static
    {
        return static::$instance ?? (static::$instance = new static(...$args));
    }
}
