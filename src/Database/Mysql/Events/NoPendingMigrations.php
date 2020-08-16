<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Events;

class NoPendingMigrations
{
    /**
     * The migration method that was called.
     *
     * @var string
     */
    public string $method;

    /**
     * Create a new event instance.
     *
     * @param string $method
     * @return void
     */
    public function __construct(string $method)
    {
        $this->method = $method;
    }
}
