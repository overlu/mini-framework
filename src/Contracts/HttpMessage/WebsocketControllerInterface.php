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
     * @param string|null $success_message
     * @param array $data
     * @return array
     */
    public function success(?string $success_message = 'succeed', array $data = []): array;

    /**
     * @param string|null $error_message
     * @param int $code
     * @return array
     */
    public function failed(?string $error_message = 'failed', $code = 0): array;

    /**
     * @param string $className
     * @param array $routeData
     * @return mixed
     */
    public function beforeDispatch(string $className, array $routeData);

    /**
     * @param $response
     * @param Frame $frame
     * @param string $className
     * @param array $routeData
     * @return mixed
     */
    public function afterDispatch($response, Frame $frame, string $className, array $routeData);

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
     * @param $fd
     * @param array $routeData
     * @return mixed
     */
    public function onClose(Server $server, $fd, array $routeData);
}
