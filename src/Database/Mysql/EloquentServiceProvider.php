<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Doctrine\DBAL\Types\Type;
use Mini\Context;
use Mini\Contracts\Queue\EntityResolver;
use Mini\Contracts\ServiceProviderInterface;
use Mini\Database\Mysql\Connectors\ConnectionFactory;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Database\Mysql\Eloquent\QueueEntityResolver;
use Mini\Database\Mysql\Events\QueryExecuted;
use Mini\Facades\Console;
use Mini\Facades\Log;
use Swoole\Server;

class EloquentServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        Model::clearBootedModels();

        $this->registerConnectionServices();
        $this->registerQueueableEntityResolver();
        $this->registerDoctrineTypes();
    }

    /**
     * Register the primary database bindings.
     *
     * @return void
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    protected function registerConnectionServices(): void
    {
        $app = app();
        $app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        $app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory'], config('database.connections', []));
        });

        $app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }


    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    protected function registerQueueableEntityResolver(): void
    {
        app()->singleton(EntityResolver::class, function () {
            return new QueueEntityResolver();
        });
    }

    /**
     * Register custom types with the Doctrine DBAL library.
     *
     * @return void
     */
    protected function registerDoctrineTypes(): void
    {
        if (!class_exists(Type::class)) {
            return;
        }

        $types = config('database.dbal.types', []);

        foreach ($types as $name => $class) {
            if (!Type::hasType($name)) {
                Type::addType($name, $class);
            }
        }
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        Model::setConnectionResolver(app('db'));

        Model::setEventDispatcher(app('events'));

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
     * @param $trigger
     *
     * @return bool
     */
    public function requestHasTrigger($trigger): bool
    {
        if (!$trigger) {
            return true;
        }

        if (RUN_ENV === 'artisan') {
            return Console::getOpt($trigger, false);
        }

        if (Context::has('IsInRequestEvent')) {
            $request = \request();
            return $request->hasHeader($trigger) || $request->has($trigger) || $request->hasCookie($trigger);
        }
        return false;
    }

    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        }

        if ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }

}
