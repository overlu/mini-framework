<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Facades\Redis;
use Mini\Service\WsServer\Socket;
use Mini\Support\Command;
use Mini\Support\Store;
use Swoole\Process;

class SocketResetCommandService extends AbstractCommandService
{
    /**
     * @param Process $process
     * @return mixed|void
     * @throws \Exception
     */
    public function handle(Process $process)
    {
        if (Command::has('bin/mini')) {
            Command::error('server is running, stop the mini server first!');
            return;
        }
        Command::info('resetting...');
        $redis = Redis::connection(config('cache . drivers . redis . collection', 'cache'));
        Store::drop(Socket::$host);
        $this->removeKeys($redis, Socket::$fdPrefix);
        $this->removeKeys($redis, Socket::$groupPrefix);
        $this->removeKeys($redis, Socket::$userPrefix);
        $this->removeKeys($redis, Socket::$userGroupPrefix);
        Command::info('done . ');
    }

    /**
     * @param \Redis $redis
     * @param $prefix
     */
    public function removeKeys($redis, $prefix): void
    {
        $it = NULL;
        while ($keys = $redis->scan($it, $prefix . '*')) {
            is_array($keys) && $redis->unlink($keys);
        }
    }

    public function getCommand(): string
    {
        return 'socket:reset';
    }

    public function getCommandDescription(): string
    {
        return 'reset socket data . ';
    }
}