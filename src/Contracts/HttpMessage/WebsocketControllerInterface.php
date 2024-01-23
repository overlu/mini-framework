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
     * @param WebsocketRequestInterface $request
     * @param array $routeData
     * @return mixed|void
     */
    public function onOpen(Server $server, WebsocketRequestInterface $request, array $routeData);

    /**
     * @param Server $server
     * @param Frame $frame
     * @param array $routeData
     * @return mixed|void
     */
    public function onMessage(Server $server, Frame $frame, array $routeData);

    /**
     * @param Server $server
     * @param int $fd
     * @param array $routeData
     * @param int $reactorId
     * @return mixed|void
     */
    public function onClose(Server $server, int $fd, array $routeData, int $reactorId);

    /**
     * @param string $action
     * @param string $success_message
     * @param mixed $data
     * @param int $code
     * @return array
     */
    public function success(string $action, mixed $data = [], string $success_message = 'succeed', int $code = 200): array;

    /**
     * @param string $action
     * @param mixed $data
     * @param string $error_message
     * @param int $code
     * @return array
     */
    public function failed(string $action, mixed $data = [], string $error_message = 'failed', int $code = 0): array;
}
