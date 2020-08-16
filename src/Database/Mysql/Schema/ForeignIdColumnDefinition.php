<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Schema;

use Mini\Support\Fluent;
use Mini\Support\Str;

class ForeignIdColumnDefinition extends ColumnDefinition
{
    /**
     * The schema builder blueprint instance.
     *
     * @var Blueprint
     */
    protected Blueprint $blueprint;

    /**
     * Create a new foreign ID column definition.
     *
     * @param Blueprint $blueprint
     * @param array $attributes
     * @return void
     */
    public function __construct(Blueprint $blueprint, array $attributes = [])
    {
        parent::__construct($attributes);

        $this->blueprint = $blueprint;
    }

    /**
     * Create a foreign key constraint on this column referencing the "id" column of the conventionally related table.
     *
     * @param string|null $table
     * @param string $column
     * @return Fluent|ForeignKeyDefinition
     */
    public function constrained(?string $table = null, string $column = 'id')
    {
        return $this->references($column)->on($table ?? Str::plural(Str::beforeLast($this->name, '_' . $column)));
    }

    /**
     * Specify which column this foreign ID references on another table.
     *
     * @param string $column
     * @return Fluent|ForeignKeyDefinition
     */
    public function references(string $column)
    {
        return $this->blueprint->foreign($this->name)->references($column);
    }
}
