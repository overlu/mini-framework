<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Database;

class ModelIdentifier
{
    /**
     * The class name of the model.
     *
     * @var string
     */
    public string $class;

    /**
     * The unique identifier of the model.
     *
     * This may be either a single ID or an array of IDs.
     *
     * @var mixed
     */
    public mixed $id;

    /**
     * The relationships loaded on the model.
     *
     * @var array
     */
    public array $relations;

    /**
     * The connection name of the model.
     *
     * @var string|null
     */
    public mixed $connection;

    /**
     * Create a new model identifier.
     *
     * @param string $class
     * @param mixed $id
     * @param array $relations
     * @param mixed $connection
     * @return void
     */
    public function __construct(string $class, mixed $id, array $relations, mixed $connection)
    {
        $this->id = $id;
        $this->class = $class;
        $this->relations = $relations;
        $this->connection = $connection;
    }
}
