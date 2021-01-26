<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Database\Mini\DB;

/**
 * Class MiniDB
 * @method static DB connection(string $name = null)
 * @method static mixed query($query, array $params = [], $mode = PDO::FETCH_ASSOC)
 * @method static array|null column($query, $params = [])
 * @method static mixed row($query, array $params = [], $mod = PDO::FETCH_ASSOC)
 * @method static mixed single($query, array $params = [])
 * @package Mini\Facades
 */
class MiniDB extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db.mini';
    }
}