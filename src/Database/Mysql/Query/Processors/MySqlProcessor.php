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
<<<<<<< HEAD
        return array_map(function ($result) {
=======
        return array_map(static function ($result) {
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
            return ((object)$result)->column_name;
        }, $results);
    }
}
