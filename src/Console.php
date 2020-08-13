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
    ];

    /**
     * @throws Exceptions\InvalidResponseException
     * @throws Throwable
     */
    public static function run(): void
    {
        try {
            BaseProviderService::getInstance()->register(null, null);
            BaseProviderService::getInstance()->boot(null, null);
            CommandService::register([...config('console', []), ...static::$systemCommandService]);
            CommandService::run();
        } catch (Throwable $throwable) {
            (new Handler($throwable))->throw();
        }
    }
}
