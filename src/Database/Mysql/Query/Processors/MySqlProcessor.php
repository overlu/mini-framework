<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Query\Processors;

class MySqlProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     *
     * @param array $results
     * @return array
     */
    public function processColumnListing(array $results):array
    {
        return array_map(static function ($result) {
            return ((object)$result)->column_name;
        }, $results);
    }
}
