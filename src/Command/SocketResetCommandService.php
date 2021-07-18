<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Service\WsServer\Socket;
use Mini\Support\Command;
use Mini\Support\Store;
use Swoole\Process;

class SocketResetCommandService extends AbstractCommandService
{
    /**
     * @param Process $process
     * @return mixed|void
     */
    public function handle(Process $process)
    {
        Command::info('resetting...');
        if (Store::drop(Socket::$host)) {
            Command::info('host reset succeed.');
        } else {
            Command::error('host reset failed.');
        }
        if (Store::drop(Socket::$fdPrefix)) {
            Command::info('fd reset succeed.');
        } else {
            Command::error('fd reset failed.');
        }
        if (Store::drop(Socket::$groupPrefix)) {
            Command::info('group reset succeed.');
        } else {
            Command::error('group reset failed.');
        }
        if (Store::drop(Socket::$userPrefix)) {
            Command::info('user reset succeed.');
        } else {
            Command::error('user reset failed.');
        }
        if (Store::drop(Socket::$userGroupPrefix)) {
            Command::info('user:group reset succeed.');
        } else {
            Command::error('user:group reset failed.');
        }
        Command::info('done.');
    }

    public function getCommand(): string
    {
        return 'socket:reset';
    }

    public function getCommandDescription(): string
    {
        return 'reset socket data.';
    }
}