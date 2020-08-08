<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */

namespace Mini\Command;

use Mini\Console\App;
use Mini\Exceptions\Handler;
use Swoole\ExitException;

class CommandService
{
    private static array $commands = [];

    /**
     * 注册command服务
     * @param $commandService
     */
    public static function register($commandService): void
    {
        foreach ((array)$commandService as $service) {
            static::$commands[$service::$command] = $service;
        }
    }

    public static function run(): void
    {
        go(static function () {
            try {
                $app = new App([
                    'desc' => 'mini cli application',
                ]);
                foreach (static::$commands as $command => $instance) {
                    $app->addCommand($command, static function () use ($instance, $app) {
                        (new $instance)->setApp($app)->run();
                    }, $instance::$description ?? null);
                }
                $app->run();
            } catch (\Throwable $throwable) {
                if (!$throwable instanceof ExitException) {
                    (new Handler($throwable))->throw();
                }
            }
        });
    }
}