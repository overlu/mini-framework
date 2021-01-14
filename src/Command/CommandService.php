<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\App;
use Mini\Exceptions\Handler;
use Swoole\ExitException;
use Swoole\Process;

class CommandService
{
    /**
     * @var BaseCommandService[]
     */
    private static array $commands = [];

    /**
     * 注册command服务
     * @param $commandService
     */
    public static function register($commandService): void
    {
        foreach ((array)$commandService as $service) {
            $service = new $service;
            static::$commands[$service->getCommand()] = $service;
        }
    }

    public static function run(): void
    {
        try {
            $app = new App([
                'desc' => 'mini cli application',
            ]);
            foreach (static::$commands as $command => $instance) {
                $app->addCommand($command, static function () use ($instance, $app) {
                    $instance->setApp($app)->run();
                }, $instance->getCommandDescription());
            }
            (new Process(function () use ($app) {
                $app->run();
            }))->start();
            Process::wait(!($app->getOpt('d') || $app->getArg('daemonize')));
        } catch (\Throwable $throwable) {
            if (!$throwable instanceof ExitException) {
                app('exception')->throw($throwable);
            }
        }
    }
}