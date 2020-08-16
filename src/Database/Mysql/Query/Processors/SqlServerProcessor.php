<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Processors;

use Exception;
use Mini\Database\Mysql\Connection;
use Mini\Database\Mysql\Query\Builder;
use RuntimeException;

class SqlServerProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param Builder $query
     * @param string $sql
     * @param array $values
     * @param string|null $sequence
     * @return int
     * @throws Exception
     */
    public function processInsertGetId(Builder $query, string $sql, array $values, ?string $sequence = null): int
    {
        $connection = $query->getConnection();

        $connection->insert($sql, $values);

        if ($connection->getConfig('odbc') === true) {
            $id = $this->processInsertGetIdForOdbc($connection);
        } else {
            $id = $connection->getPdo()->lastInsertId();
        }

        return is_numeric($id) ? (int)$id : $id;
    }

    /**
     * Process an "insert get ID" query for ODBC.
     *
     * @param Connection $connection
     * @return int
     *
     * @throws Exception
     */
    protected function processInsertGetIdForOdbc(Connection $connection): int
    {
        $result = $connection->selectFromWriteConnection(
            'SELECT CAST(COALESCE(SCOPE_IDENTITY(), @@IDENTITY) AS int) AS insertid'
        );

        if (!$result) {
            throw new RuntimeException('Unable to retrieve lastInsertID for ODBC.');
        }

        $row = $result[0];

        return is_object($row) ? $row->insertid : $row['insertid'];
    }

    /**
     * Process the results of a column listing query.
     *
     * @param array $results
     * @return array
     */
    public function processColumnListing(array $results): array
    {
        return array_map(static function ($result) {
            return ((object)$result)->name;
        }, $results);
    }
}
