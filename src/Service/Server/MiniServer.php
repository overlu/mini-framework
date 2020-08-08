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
            if (isset($server['ip'], $server['port'], Application::$mapping[$key])) {
                $process = new Process(static function () use ($key) {
                    new Application::$mapping[$key]();
                });
                $process->start();
            }
        }
        Process::wait();
    }
}
