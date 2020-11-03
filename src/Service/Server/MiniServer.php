<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Application;
use Swoole\Process;

class MiniServer
{
    public function __construct()
    {
        $servers = config('servers', []);
        foreach ($servers as $key => $server) {
            if (isset($server['ip'], $server['port'])) {
                $process = new Process(static function () use ($key) {
                    $server = Application::$mapping[$key] ?? CustomServer::class;
                    new $server($key);
                });
                $process->start();
            }
        }
        Process::wait();
    }
}
