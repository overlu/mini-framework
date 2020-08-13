<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
namespace Mini\Database\Mysql\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config);
}
