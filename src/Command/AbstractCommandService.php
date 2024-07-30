<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\App;
use Mini\Console\Cli;
use Mini\Contracts\Console\CommandInterface;
use Mini\Support\Command;
use Swoole\Process;

/**
 * Class AbstractCommandService
 * @package Mini\Command
 * @mixin App
 */
abstract class AbstractCommandService implements CommandInterface
{
    protected App $app;

    public bool $enableCoroutine = false;

    protected string $type = '';

    public function __construct()
    {
    }

    /**
     * run console
     * @param Process|null $process
     * @return mixed
     */
    abstract public function handle(?Process $process): mixed;

    /**
     * get command
     * @return string
     */
    abstract public function getCommand(): string;

    /**
     * get command description
     * @return string
     */
    abstract public function getCommandDescription(): string;

    /**
     * @param App $app
     * @return $this
     */
    public function setApp(App $app): self
    {
        $this->app = $app;
        return $this;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->app->$name(...$arguments);
    }

    public function output(string $shell, Process $process = null): void
    {
        if ($this->getOpt('root')) {
            $shell .= ' -o become=root';
        }

        if (!$process) {
            $process = new Process(function () {
                $this->info('create new process.');
            });
        }
        $process->exec('/bin/sh', ['-c', $shell]);

        // 启动子进程
        $process->start();

        // 异步读取子进程的标准输出
        Process::signal(SIGCHLD, static function ($sig) {
            while ($ret = Process::wait(false)) {
                Command::info("Process {$ret['pid']} exited with status {$ret['code']}");
            }
        });

        // 实时输出子进程的标准输出
        swoole_event_add($process->pipe, function ($pipe) use ($process) {
            $data = $process->read();
            if ($data === '') {
                swoole_event_del($pipe);
                $process->close();
            } else {
                Cli::write($data);
            }
        });
    }
}
