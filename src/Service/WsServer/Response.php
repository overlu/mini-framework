<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use JsonException;
use Mini\Contracts\HttpMessage\WebsocketResponseInterface;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;

/**
 * Class Response
 * @package Mini\Service\WsServer
 */
class Response implements WebsocketResponseInterface
{
    private Server $server;
    private $fd;
    private Request $request;

    public function __construct(Request $request, Server $server)
    {
        $this->request = $request;
        $this->fd = $request->fd;
        $this->server = $server;
    }

    /**
     * @param $data
     * @param null $fd
     * @return $this
     * @throws JsonException
     */
    public function push($data, $fd = null): WebsocketResponseInterface
    {
        $data = $this->transferToResponse($data);
        $this->server->push(is_null($fd) ? $this->fd : $fd, $data);
        return $this;
    }

    /**
     * @param null $fd
     */
    public function close($fd = null): void
    {
        $this->server->close(is_null($fd) ? $this->fd : $fd);
    }

    /**
     * @param $response
     * @return false|string
     * @throws JsonException
     */
    private function transferToResponse($response)
    {
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
            return method_exists($response, '__toString') ? (string)$response : json_encode((array)$response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        return (string)$response;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @return Request
     */
    public function getSwooleRequest(): Request
    {
        return $this->request;
    }
}