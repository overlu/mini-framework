<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Processors;

use Mini\Database\Mysql\Query\Builder;

class Processor
{
    /**
     * Process the results of a "select" query.
     *
<<<<<<< HEAD
     * @param \Mini\Database\Mysql\Query\Builder $query
=======
     * @param Builder $query
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @param array $results
     * @return array
     */
    public function processSelect(Builder $query, array $results): array
    {
        return $results;
    }

    /**
     * Process an  "insert get ID" query.
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
     */
    public function processInsertGetId(Builder $query, string $sql, array $values, ?string $sequence = null): int
    {
        $query->getConnection()->insert($sql, $values);

        $id = $query->getConnection()->getPdo()->lastInsertId($sequence);

        return is_numeric($id) ? (int)$id : $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * @param array $results
     * @return array
     */
    public function processColumnListing(array $results): array
    {
        return $results;
    }
}
