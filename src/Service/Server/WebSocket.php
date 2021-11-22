<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Service\HttpServer\RouteService;
use Swoole\Server as HttpSwooleServer;
use Swoole\WebSocket\Server;
use Throwable;

class WebSocket extends AbstractServer
{
    use WebSocketTrait;

    protected string $type = 'webSocket';

    /**
     * @var RouteService
     */
    protected RouteService $route;

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
    }

    /**
     * @param HttpSwooleServer $server
     * @param int $workerId
     * @throws Throwable
     */
    public function onWorkerStart(HttpSwooleServer $server, int $workerId): void
    {
        parent::onWorkerStart($server, $workerId);
        try {
            $this->route = RouteService::getInstance();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }
}
