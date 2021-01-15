<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Support\Str;
use PDOException;
use Throwable;

trait DetectsConcurrencyErrors
{
    /**
     * Determine if the given exception was caused by a concurrency error such as a deadlock or serialization failure.
     *
<<<<<<< HEAD
     * @param \Throwable $e
=======
     * @param Throwable $e
>>>>>>> 4750aa4bbb44323ff0e45e46f537d3183c82b9be
     * @return bool
     */
    protected function causedByConcurrencyError(Throwable $e): bool
    {
        if ($e instanceof PDOException && $e->getCode() === '40001') {
            return true;
        }

        $message = $e->getMessage();

        return Str::contains($message, [
            'Deadlock found when trying to get lock',
            'deadlock detected',
            'The database file is locked',
            'database is locked',
            'database table is locked',
            'A table in the database is locked',
            'has been chosen as the deadlock victim',
            'Lock wait timeout exceeded; try restarting transaction',
            'WSREP detected deadlock/conflict and aborted the transaction. Try restarting the transaction',
        ]);
    }
}
