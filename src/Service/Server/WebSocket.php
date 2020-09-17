<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Swoole\WebSocket\Server;

class WebSocket extends HttpServer
{
    protected string $type = 'WebSocket';

    public function initialize(): void
    {
        $this->config = config('servers.ws');
        $this->worker_num = $this->config['settings']['worker_num'] ?? 1;
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
        $this->server->on('request', [$this, 'onRequest']);
        \Mini\Server::getInstance()->set(self::class, $this->server);
    }
}
