<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

trait Tappable
{
    /**
     * Call the given Closure with this instance then return the instance.
     *
     * @param callable|null $callback
     * @return mixed
     */
    public function tap(callable $callback = null): mixed
    {
        return tap($this, $callback);
    }
}
