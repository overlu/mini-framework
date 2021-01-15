<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Command\CommandService;
use Mini\Command\MakeMigrationCommandService;
use Mini\Command\MigrateCommandService;
use Mini\Command\MigrateInstallCommandService;
use Mini\Command\MigrateRefreshCommandService;
use Mini\Command\MigrateResetCommandService;
use Mini\Command\MigrateRollbackCommandService;
use Mini\Command\RunCrontabCommandService;
use Mini\Command\StatusCrontabCommandService;
use Mini\Command\StorageLinkCommandService;
use Mini\Exceptions\Handler;
use Mini\Provider\BaseProviderService;
use Throwable;

class Console
{
    private static array $systemCommandService = [
        MigrateCommandService::class,
        MigrateInstallCommandService::class,
        MigrateResetCommandService::class,
        MigrateRollbackCommandService::class,
        MigrateRefreshCommandService::class,
        MakeMigrationCommandService::class,
        StorageLinkCommandService::class,
        RunCrontabCommandService::class,
        StatusCrontabCommandService::class
    ];

    /**
     * @throws Throwable
     */
    public static function run(): void
    {
        self::initial();
        try {
            \SeasLog::setRequestID(uniqid('', true));
            BaseProviderService::getInstance()->register(null, null);
            BaseProviderService::getInstance()->boot(null, null);
            CommandService::register([...config('console', []), ...static::$systemCommandService]);
            CommandService::run();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    private static function initial()
    {
        ini_set('display_errors', config('app.debug') === true ? 'on' : 'off');
        ini_set('display_startup_errors', 'on');
        ini_set('date.timezone', config('app.timezone', 'UTC'));
//        error_reporting(env('APP_ENV', 'local') === 'production' ? 0 : E_ALL);
        error_reporting(E_ALL);
    }
}
