<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Events;

use Mini\Contracts\Database\Events\MigrationEvent as MigrationEventContract;
use Mini\Database\Mysql\Migrations\Migration;

abstract class MigrationEvent implements MigrationEventContract
{
    /**
     * An migration instance.
     *
     * @var Migration
     */
    public Migration $migration;

    /**
     * The migration method that was called.
     *
     * @var string
     */
    public string $method;

    /**
     * Create a new event instance.
     *
     * @param Migration $migration
     * @param string $method
     * @return void
     */
    public function __construct(Migration $migration, string $method)
    {
        $this->method = $method;
        $this->migration = $migration;
    }
}
