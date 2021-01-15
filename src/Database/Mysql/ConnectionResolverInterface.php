<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

interface ConnectionResolverInterface
{
    /**
     * Get a database connection instance.
     *
     * @param string|null $name
<<<<<<< HEAD
     * @return \Mini\Database\Mysql\ConnectionInterface
=======
     * @return ConnectionInterface
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     */
    public function connection(?string $name = null): ConnectionInterface;

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string;

    /**
     * Set the default connection name.
     *
     * @param string $name
     * @return void
     */
    public function setDefaultConnection(string $name): void;
}
