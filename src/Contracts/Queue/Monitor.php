<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

interface Monitor
{
    /**
     * Register a callback to be executed on every iteration through the queue loop.
     *
     * @param mixed $callback
     * @return void
     */
    public function looping(mixed $callback): void;

    /**
     * Register a callback to be executed when a job fails after the maximum amount of retries.
     *
     * @param mixed $callback
     * @return void
     */
    public function failing(mixed $callback): void;

    /**
     * Register a callback to be executed when a daemon queue is stopping.
     *
     * @param mixed $callback
     * @return void
     */
    public function stopping(mixed $callback): void;
}
