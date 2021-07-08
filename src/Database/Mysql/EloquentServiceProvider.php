<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Context;
use Mini\Contracts\ServiceProviderInterface;
use Mini\Database\Mysql\Events\QueryExecuted;
use Mini\Facades\Console;
use Mini\Facades\Log;
use Mini\Facades\Request;
use Swoole\Server;

class EloquentServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        $config = config('database.connections', []);
        if (!empty($config)) {
            /**
             * @url https://github.com/Mini/database
             */
            new DatabaseBoot($config);
        }
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        if (!config('logging.database_query_log_enabled', false) || config('app.env') === 'production') {
            return;
        }

        $trigger = config('logging.database_query_log_trigger', false);

        if (!$this->requestHasTrigger($trigger)) {
            return;
        }

        DB::listen(function (QueryExecuted $query) {
            if ($query->time < config('logging.slower_than', 0)) {
                return;
            }

            $sqlWithPlaceholders = str_replace(['%', '?', '%s%s'], ['%%', '%s', '?'], $query->sql);

            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo = $query->connection->getPdo();
            $realSql = $sqlWithPlaceholders;
            $duration = $this->formatDuration($query->time / 1000);

            if (count($bindings) > 0) {
                $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
            }
            if (Context::has('IsInRequestEvent')) {
                $request = request();
                Log::debug(sprintf('[%s] [%s] %s | %s: %s', $query->connection->getDatabaseName(), $duration, $realSql,
                    $request->getMethod(), $request->getRequestUri()), [], 'query');
                return;
            }
            if (RUN_ENV === 'artisan') {
                Log::debug(sprintf('[%s] [%s] %s | %s: %s', $query->connection->getDatabaseName(), $duration, $realSql,
                    'artisan', Console::getCommand()), [], 'query');
                return;
            }
        });
    }

    /**
     * @param string $trigger
     *
     * @return bool
     */
    public function requestHasTrigger($trigger)
    {
        if (!$trigger) {
            return true;
        }
        if (Context::has('IsInRequestEvent')) {
            $request = \request();
            return $request->hasHeader($trigger) || $request->has($trigger) || $request->hasCookie($trigger);
        }
        if (RUN_ENV === 'artisan') {
            return Console::getOpt($trigger, false);
        }
    }

    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }

}
