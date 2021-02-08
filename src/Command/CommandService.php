<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\App;
use RuntimeException;
use Swoole\ExitException;
use Swoole\Process;

class CommandService
{
    /**
     * @var AbstractCommandService[]
     */
    private static array $commands = [];

    /**
     * 注册command服务
     * @param AbstractCommandService[]|AbstractCommandService $commandService
     */
    public static function register($commandService): void
    {
        foreach ((array)$commandService as $service) {
            if (!($service = new $service) || !$service instanceof AbstractCommandService) {
                throw new RuntimeException(get_class($service) . ' should instance of ' . AbstractCommandService::class);
            }
            static::$commands[$service->getCommand()] = $service;
        }
    }

    public static function run(): void
    {
        try {
            $app = new App([
                'desc' => 'mini cli application',
            ]);
            $process = new Process(function () use ($app) {
                $app->run();
            });
            foreach (static::$commands as $command => $instance) {
                $app->addCommand($command, static function () use ($instance, $app, $process) {
                    $instance->setApp($app)->handle($process);
                }, $instance->getCommandDescription());
            }
            $process->start();
            Process::wait(!($app->getOpt('d') || $app->getArg('daemonize')));
        } catch (\Throwable $throwable) {
            if (!$throwable instanceof ExitException) {
                app('exception')->throw($throwable);
            }
        }
    }
}