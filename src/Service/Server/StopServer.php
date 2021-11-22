<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Application;
use Mini\Console\App;
use Mini\Support\Command;
use Swoole\Process;

class StopServer
{
    private bool $force;

    public function __construct(string $server = 'all')
    {
        run(function () use ($server) {
            $this->force = (new App())->getOpt('force', false);
            if (strtolower($server) !== 'all') {
                $this->stopServer($server);
            } else {
                $this->stopAllServer();
            }
        });
    }

    /**
     * @param string $server
     * @throws \Exception
     */
    public function stopServer(string $server): void
    {
        if ($this->force || (is_dev_env(true) && config('app.hot_reload', false))) {
            $this->forceStopServer($server);
            return;
        }
        $pidFile = config('servers.' . $server . '.settings.pid_file', runtime_path($server . '.server.pid'));
        if (file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);
            $result = Process::kill($pid, 0);
            if ($result) {
                Process::kill($pid);
                $time = time();
                while (true) {
                    usleep(1000);
                    if (!Process::kill($pid, 0)) {
                        if (is_file($pidFile)) {
                            @unlink($pidFile);
                        }
                        Command::infoWithTime('stop ' . $server . ' server succeed.');
                        break;
                    }

                    if (time() - $time > 5) {
                        Command::error("stop server failed , use [--force] again");
                        break;
                    }
                }
            } else {
                Command::error('no ' . $server . ' server running.');
                @unlink($pidFile);
            }
        } else {
            Command::error('no ' . $server . ' server running, check whether running in the daemon model.');
        }


    }

    /**
     * @throws \Exception
     */
    public function stopAllServer(): void
    {
        $servers = config('servers', []);
        foreach ($servers as $key => $server) {
            $this->stopServer($key);
        }
        Command::line();
        Command::infoWithTime('stop all server finished.');
    }

    /**
     * @param string $server
     * @throws \Exception
     */
    private function forceStopServer(string $server): void
    {
        $process = 'bin/mini start ' . $server;
        $pidFile = config('servers.' . $server . '.settings.pid_file', runtime_path($server . '.server.pid'));
        if (file_exists($pidFile)) {
            @unlink($pidFile);
        }
        if ((!$server || isset(Application::$mapping[$server])) && Command::has($process)) {
            Command::kill($process);
            Command::infoWithTime('stop ' . $server . ' server succeed.');
        }
    }
}