<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 * @date 2024/5/24 15:55
 */
declare(strict_types=1);

namespace Mini\Events;

trait Dispatchable
{
    /**
     * Dispatch the event with the given arguments.
     * @return array|null
     */
    public static function dispatch(): ?array
    {
        return event(new static(...func_get_args()));
    }

    /**
     * Dispatch the event with the given arguments if the given truth test passes.
     * @param bool $boolean
     * @param mixed ...$arguments
     * @return array|null
     */
    public static function dispatchIf(bool $boolean, ...$arguments): ?array
    {
        if ($boolean) {
            return event(new static(...$arguments));
        }
        return null;
    }

    /**
     * Dispatch the event with the given arguments unless the given truth test passes.
     * @param bool $boolean
     * @param mixed ...$arguments
     * @return array|null
     */
    public static function dispatchUnless(bool $boolean, ...$arguments): ?array
    {
        if (!$boolean) {
            return event(new static(...$arguments));
        }
        return null;
    }
}