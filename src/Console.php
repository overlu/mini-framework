<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Command\CommandService;
use Mini\Command\KeyGenerateCommandService;
use Mini\Command\MakeMigrationCommandService;
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
use Mini\Command\VendorPublishCommandService;
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
        StatusCrontabCommandService::class,
        SocketResetCommandService::class,
        VendorPublishCommandService::class,
        KeyGenerateCommandService::class,
        RoutesAllCommandService::class,
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
