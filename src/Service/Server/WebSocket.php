<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Swoole\WebSocket\Server;

class WebSocket extends AbstractServer
{
    protected string $type = 'WebSocket';

    public function initialize(): void
    {
        $this->config = config('servers.ws');
        $this->worker_num = $this->config['settings']['worker_num'] ?? 1;
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
        \Mini\Server::getInstance()->set(self::class, $this->server);
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId): void
    {
//            $this->route = Route::getInstance();
        parent::onWorkerStart($server, $workerId);
    }
}
