<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Processors;

use Mini\Database\Mysql\Query\Builder;

class PostgresProcessor extends Processor
{
    /**
     * Process an "insert get ID" query.
     *
     * @param \Mini\Database\Mysql\Query\Builder $query
     * @param string $sql
     * @param array $values
     * @param string|null $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        $connection->recordsHaveBeenModified();

        $result = $connection->selectFromWriteConnection($sql, $values)[0];

        $sequence = $sequence ?: 'id';

        $id = is_object($result) ? $result->{$sequence} : $result[$sequence];

        return is_numeric($id) ? (int)$id : $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * @param array $results
     * @return array
     */
    public function processColumnListing($results)
    {
        return array_map(function ($result) {
            return ((object)$result)->column_name;
        }, $results);
    }
}
