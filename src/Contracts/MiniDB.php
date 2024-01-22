<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use Mini\Database\Mini\DB;

/**
 * @method DB connection(string $name = null)
 * @method mixed query($query, array $params = [], $mode = \PDO::FETCH_ASSOC)
 * @method array|null column($query, $params = [])
 * @method mixed row($query, array $params = [], $mod = \PDO::FETCH_ASSOC)
 * @method mixed single($query, array $params = [])
 */
interface MiniDB
{
}
