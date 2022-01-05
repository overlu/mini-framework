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
    use WebSocketTrait;

    protected string $type = 'webSocket';

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
    }
}
