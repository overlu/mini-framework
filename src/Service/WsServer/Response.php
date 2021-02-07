<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Contracts\View\View;
use Swoole\WebSocket\Server;

class Response
{
    private Server $server;
    private $fd;

    public function __construct($request, Server $server)
    {
        $this->fd = $request->fd;
        $this->server = $server;
    }

    public function push($data)
    {
        $data = $this->transferToResponse($data);
        $this->server->push($this->fd, $data);
    }

    private function transferToResponse($response)
    {
        if ($response instanceof View) {
            return $response->render();
        }

        if ($response instanceof Arrayable) {
            $response = $response->toArray();
        }

        if (is_array($response)) {
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if ($response instanceof Jsonable) {
            return $response->toJson();
        }

        if (is_object($response)) {
            return json_encode((array)$response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }
}