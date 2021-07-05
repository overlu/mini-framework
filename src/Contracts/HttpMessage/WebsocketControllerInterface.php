<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

interface WebsocketControllerInterface
{
    /**
     * @param Server $server
     * @param \Swoole\Http\Request $request
     * @param array $routeData
     * @return mixed
     */
    public function onOpen(Server $server, \Swoole\Http\Request $request, array $routeData);

    /**
     * @param Server $server
     * @param Frame $frame
     * @param array $routeData
     * @return mixed
     */
    public function onMessage(Server $server, Frame $frame, array $routeData);

    /**
     * @param Server $server
     * @param int $fd
     * @param array $routeData
     * @param int $reactorId
     * @return mixed
     */
    public function onClose(Server $server, int $fd, array $routeData, int $reactorId);
}
