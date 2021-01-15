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
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Connection $connection
=======
     * @param Connection $connection
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
<<<<<<< HEAD
            throw new Exception('Unable to retrieve lastInsertID for ODBC.');
=======
            throw new RuntimeException('Unable to retrieve lastInsertID for ODBC.');
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
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
<<<<<<< HEAD
        return array_map(function ($result) {
=======
        return array_map(static function ($result) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            return ((object)$result)->name;
        }, $results);
    }
}
