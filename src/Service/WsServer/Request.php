<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Swoole\WebSocket\Server;

class Request
{
    private $request;

    public function __construct($request, Server $server)
    {
        $this->request = $request;
    }

    public function query($key = null, $default = null)
    {
        return $key === null ? $this->request->get : ($this->request->get[$key] ?? $default);
    }

    public function all()
    {
        return ($this->request->get ?? []) + ($this->request->post ?? []);
    }

    public function input($key = null, $default = null)
    {
        $data = $this->all();
        return $key === null ? $data : ($data[$key] ?? $default);
    }

    public function getMethod()
    {
        return 'GET';
    }

    public function __call($name, $arguments)
    {
        return null;
    }
}