<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\HttpMessage\WebsocketRequestInterface;
use Swoole\WebSocket\Server;

/**
 * Class Request
 * @package Mini\Service\WsServer
 */
class Request implements WebsocketRequestInterface
{
    private \Swoole\Http\Request $request;
    private Server $server;

    public function __construct(\Swoole\Http\Request $request, Server $server)
    {
        $this->request = $request;
        $this->server = $server;
    }

    /**
     * Retrieve the data from query parameters, if $key is null, will return all query parameters.
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, $default = null)
    {
        return $key === null ? $this->request->get : ($this->request->get[$key] ?? $default);
    }

    /**
     * Retrieve the data from parsed body, if $key is null, will return all parsed body.
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, $default = null)
    {
        return $key === null ? $this->request->post : ($this->request->post[$key] ?? $default);
    }

    /**
     * Retrieve all input data from request, include query parameters, parsed body and json body.
     */
    public function all(): array
    {
        return ($this->request->get ?? []) + ($this->request->post ?? []);
    }

    /**
     * Retrieve the input data from request, include query parameters, parsed body and json body.
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        $data = $this->all();
        return $data[$key] ?? $default;
    }

    /**
     * Determine if the $keys is exist in parameters.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->request->get[$key]) || isset($this->request->post[$key]);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return 'GET';
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return $this->request->fd;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getSwooleRequest(): \Swoole\Http\Request
    {
        return $this->request;
    }
}