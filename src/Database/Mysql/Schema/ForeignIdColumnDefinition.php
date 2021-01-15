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
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Schema\Blueprint $blueprint
=======
     * @param Blueprint $blueprint
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
<<<<<<< HEAD
     * @return \Mini\Support\Fluent|\Mini\Database\Mysql\Schema\ForeignKeyDefinition
=======
     * @return Fluent|ForeignKeyDefinition
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function constrained(?string $table = null, string $column = 'id')
    {
        return $this->references($column)->on($table ?? Str::plural(Str::beforeLast($this->name, '_' . $column)));
    }

    /**
     * Specify which column this foreign ID references on another table.
     *
     * @param string $column
<<<<<<< HEAD
     * @return \Mini\Support\Fluent|\Mini\Database\Mysql\Schema\ForeignKeyDefinition
=======
     * @return Fluent|ForeignKeyDefinition
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function references(string $column)
    {
        return $this->blueprint->foreign($this->name)->references($column);
    }
}
