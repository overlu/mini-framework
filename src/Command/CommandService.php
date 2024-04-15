<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Contracts\Container\BindingResolutionException;
use RuntimeException;
use Swoole\ExitException;
use Swoole\Process;
use Throwable;

class CommandService
{
    /**
     * @var AbstractCommandService[]
     */
    private static array $commands = [];

    /**
     * 注册command服务
     * @param AbstractCommandService|AbstractCommandService[] $commandService
     */
    public static function register(array|AbstractCommandService $commandService): void
    {
        foreach ((array)$commandService as $service) {
            if (!($service = new $service) || !$service instanceof AbstractCommandService) {
                throw new RuntimeException(get_class($service) . ' should instance of ' . AbstractCommandService::class);
            }
            static::$commands[$service->getCommand()] = $service;
        }
    }

    /**
     * @return AbstractCommandService[]
     */
    public static function getRegisterCommands(): array
    {
        return static::$commands;
    }

    /**
     * @throws BindingResolutionException|Throwable
     */
    public static function run(): void
    {
        try {
            $console = app('console');
            $console->process->start();
            Process::wait(!($console->app->getOpt('d') || $console->app->getArg('daemonize')));
        } catch (Throwable $throwable) {
            if (!$throwable instanceof ExitException) {
                throw $throwable;
            }
        }
    }
}
