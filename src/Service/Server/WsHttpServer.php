<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Swoole\WebSocket\Server;

/**
 * Class WsHttpServer
 * @package Mini\Service\Server
 */
class WsHttpServer extends HttpServer
{
    use WebSocketTrait;

    protected string $type = 'WebSocket And Http';

    /**
     * @var array|callable
     */
    private $handler = null;

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode'], $this->config['sock_type']);
    }
}
