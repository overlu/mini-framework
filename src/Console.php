<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Command\CommandService;
use Mini\Command\DeployCommandService;
use Mini\Command\DeployRollbackCommandService;
use Mini\Command\DeployUnlockCommandService;
use Mini\Command\HelloMiniCommandService;
use Mini\Command\IdeHelperModelCommandService;
use Mini\Command\KeyGenerateCommandService;
use Mini\Command\LogStatusCommandService;
use Mini\Command\MakeCommandCommandService;
use Mini\Command\MakeControllerCommandService;
use Mini\Command\MakeCrontabCommandService;
use Mini\Command\MakeEventCommandService;
use Mini\Command\MakeListenerCommandService;
use Mini\Command\MakeMailCommandService;
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
use Mini\Command\ViewCacheCommandService;
use Mini\Command\ViewClearCommandService;
use Throwable;

class Console
{
    public static array $systemCommandService = [
        HelloMiniCommandService::class,
        KeyGenerateCommandService::class,
        DeployCommandService::class,
        DeployUnlockCommandService::class,
        DeployRollbackCommandService::class,
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
        MakeMailCommandService::class,
        StorageLinkCommandService::class,
        RunCrontabCommandService::class,
        StatusCrontabCommandService::class,
        SocketResetCommandService::class,
        VendorPublishCommandService::class,
        ViewClearCommandService::class,
        ViewCacheCommandService::class,
        IdeHelperModelCommandService::class
    ];

    /**
     * @throws Throwable
     */
    public static function run(): void
    {
        try {
            Bootstrap::initial();
            Bootstrap::getInstance()->consoleStart();
//            CommandService::register([...config('console', []), ...static::$systemCommandService]);
            CommandService::run();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }
}
