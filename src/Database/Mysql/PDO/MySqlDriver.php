<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\PDO;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Mini\Database\Mysql\PDO\Concerns\ConnectsToDatabase;

class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'pdo_mysql';
    }
}
