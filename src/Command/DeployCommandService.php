<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Console\Cli;
use Mini\Support\Command;
use Swoole\Process;

class DeployCommandService extends AbstractCommandService
{
    /**
     * @param Process|null $process
     * @return bool
     */
    public function handle(?Process $process): bool
    {
        $env = $this->getFirstArg();
        if (empty($env)) {
            $this->error('Missing environment');
            return false;
        }
        $shell = 'vendor/bin/dep deploy env=' . $env;
        if ($this->getOpt('root')) {
            $shell .= ' -o become=root';
        }
//        Command::exec($shell);
        if (!$process) {
            $this->error('process not found');
            return false;
        }
        $process->exec('/bin/sh', ['-c', $shell]);

        // 启动子进程
        $process->start();

        // 异步读取子进程的标准输出
        Process::signal(SIGCHLD, static function ($sig) {
            while ($ret = Process::wait(false)) {
                $this->info("Process {$ret['pid']} exited with status {$ret['code']}");
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

        return true;
    }

    public function getCommand(): string
    {
        return 'deploy';
    }

    public function getCommandDescription(): string
    {
        return 'deploy mini server.
                   <blue>{[env]} : The server environment.}
                   {--root : Use sudo deploy the mini server}</blue>';
    }
}