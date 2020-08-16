<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Connectors;

use PDO;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @param array $config
     * @return PDO
     */
    public function connect(array $config): PDO;
}
