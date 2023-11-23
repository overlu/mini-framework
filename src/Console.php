<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Command\CommandService;
use Mini\Command\HelloMiniCommandService;
use Mini\Command\KeyGenerateCommandService;
use Mini\Command\LogStatusCommandService;
use Mini\Command\MakeCommandCommandService;
use Mini\Command\MakeControllerCommandService;
use Mini\Command\MakeCrontabCommandService;
use Mini\Command\MakeEventCommandService;
use Mini\Command\MakeListenerCommandService;
use Mini\Command\MakeMiddlewareCommandService;
use Mini\Command\MakeMigrationCommandService;
use Mini\Command\MakeModelCommandService;
use Mini\Command\MakeObserverCommandService;
use Mini\Command\MakeProviderCommandService;
use Mini\Command\MigrateCommandService;
use Mini\Command\MigrateInstallCommandService;
use Mini\Command\MigrateRefreshCommandService;
use Mini\Command\MigrateResetCommandService;
use Mini\Command\MigrateRollbackCommandService;
use Mini\Command\RoutesAllCommandService;
use Mini\Command\RunCrontabCommandService;
use Mini\Command\SocketResetCommandService;
use Mini\Command\StatusCrontabCommandService;
use Mini\Command\StorageLinkCommandService;
use Mini\Command\TestCommandService;
use Mini\Command\VendorPublishCommandService;
use Throwable;

class Console
{
    private static array $systemCommandService = [
        HelloMiniCommandService::class,
        KeyGenerateCommandService::class,
        RoutesAllCommandService::class,
        TestCommandService::class,
        LogStatusCommandService::class,
        MigrateCommandService::class,
        MigrateInstallCommandService::class,
        MigrateResetCommandService::class,
        MigrateRollbackCommandService::class,
        MigrateRefreshCommandService::class,
        MakeMigrationCommandService::class,
        MakeCommandCommandService::class,
        MakeControllerCommandService::class,
        MakeCrontabCommandService::class,
        MakeEventCommandService::class,
        MakeListenerCommandService::class,
        MakeMiddlewareCommandService::class,
        MakeModelCommandService::class,
        MakeObserverCommandService::class,
        MakeProviderCommandService::class,
        StorageLinkCommandService::class,
        RunCrontabCommandService::class,
        StatusCrontabCommandService::class,
        SocketResetCommandService::class,
        VendorPublishCommandService::class,
    ];

    /**
     * @throws Throwable
     */
    public static function run(): void
    {
        try {
            Bootstrap::initial();
            Bootstrap::getInstance()->consoleStart();
            CommandService::register([...config('console', []), ...static::$systemCommandService]);
            CommandService::run();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }
}
