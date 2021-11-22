<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Support\Command;
use Swoole\Process;

class ReloadServer
{
    public function __construct(string $server = 'all')
    {
        run(function () use ($server) {
            if (strtolower($server) !== 'all') {
                $this->reloadServer($server);
            } else {
                $this->reloadAllServer();
            }
        });
    }

    /**
     * @param string $server
     * @throws \Exception
     */
    public function reloadServer(string $server): void
    {
        $pidFile = config('servers.' . $server . '.settings.pid_file', runtime_path($server . '.server.pid'));
        if (file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);
            $result = Process::kill($pid, 0);
            if ($result) {
                Process::kill($pid, SIGUSR1);
                Command::infoWithTime('reload ' . $server . ' server succeed.');
            } else {
                Command::error('no ' . $server . ' server running.');
                unlink($pidFile);
            }
        } else {
            Command::error('no ' . $server . ' server running, check whether running in the daemon model.');
        }


    }

    /**
     * @throws \Exception
     */
    public function reloadAllServer(): void
    {
        $servers = config('servers', []);
        foreach ($servers as $key => $server) {
            $this->reloadServer($key);
        }
        Command::line();
        Command::infoWithTime('reload all server finished.');
    }
}