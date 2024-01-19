<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param string|null $name
     * @return Queue
     */
    public function connection(string $name = null): Queue;
}
