<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Command\CommandExecute;
use Mini\Command\CommandService;
use Mini\Service\AbstractServiceProvider;
use Swoole\Process;

/**
 * Class ConsoleServiceProvider
 * @package Mini\Console
 */
class ConsoleServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        CommandService::register([...config('console', []), ...\Mini\Console::$systemCommandService]);
        $this->app->singleton('console', function () {
            $app = new App([
                'desc' => 'mini cli application',
            ]);
            $console = new Console();
            $console->setApp($app);
            if (RUN_ENV === 'artisan') {
                $process = new Process(function () use ($app) {
                    $app->run();
                });
            } else {
                $process = null;
            }

            $commands = CommandService::getRegisterCommands();
            foreach ($commands as $command => $instance) {
                $app->addCommand($command, (new CommandExecute($command, $instance, $instance->enableCoroutine))->setApp($app)->setProcess($process), $instance->getCommandDescription());
            }
            $res = new \stdClass();
            $res->app = $app;
            $res->process = $process;
            $res->console = $console;
            return $res;
        });
        $this->app->singleton('console.console', function () {
            return $this->app['console']->console;
        });
        $this->app->singleton('console.process', function () {
            return $this->app['console']->process;
        });
        $this->app->singleton('console.app', function () {
            return $this->app['console']->app;
        });
    }

    public function boot(): void
    {
    }
}
