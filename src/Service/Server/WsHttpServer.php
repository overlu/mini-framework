<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Context;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Contracts\Support\Sendable;
use Mini\Exceptions\Handler;
use Mini\Provider\BaseRequestService;
use Mini\Service\HttpMessage\Server\Request as Psr7Request;
use Mini\Service\HttpMessage\Stream\SwooleStream;
use Mini\Service\WsServer\Request;
use Mini\Service\WsServer\Response;
use Mini\View\View;
use Swoole\WebSocket\Server;

class WsHttpServer extends HttpServer
{
    protected string $type = 'WebSocket And Http';

    /**
     * @var array|callable
     */
    private $handler = null;

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
    }

    public function onOpen(Server $server, $request)
    {
        try {
            $this->initWsRequestAndResponse($request, $server);
            $resp = $this->route->dispatchWs($request);

            if (is_array($resp) && isset($resp['class'])) {
                $this->handler = [
                    'callable' => [new $resp['class'], $resp['method']],
                    'data' => $resp['data']
                ];
                return;
            }
            if (is_array($resp) && isset($resp['callbale'])) {
                $this->handler = $resp;
                return;
            }
            if (is_array($resp) && isset($resp['error'])) {
                $server->push($request->fd, ws_error_format($resp['error'], $resp['code'] ?? 0));
            }
            if (is_string($resp)) {
                $server->push($request->fd, ws_error_format($resp));
            }
            $server->close($request->fd);
        } catch (Throwable $throwable) {
            $server->close($request->fd);
            (new Handler($throwable))->throw();
        }
    }

    public function onMessage(Server $server, $frame)
    {
        if ($this->handler) {
            $wsResponse = $this->transferToWsResponse(call($this->handler['callable'], [$this->handler['data'], $frame, $server]));
            response()->push($wsResponse);
        }
    }

    protected function initWsRequestAndResponse($request, Server $server)
    {
        app()->offsetSet(RequestInterface::class, new Request($request, $server));
        app()->offsetSet(ResponseInterface::class, new Response($request, $server));
//        $this->initialProvider();
    }

    protected function transferToWsResponse($response)
    {
        if ($response instanceof Arrayable) {
            return $response->toArray();
        }

        if (is_array($response)) {
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if ($response instanceof Jsonable) {
            return (string)$response->toJson();
        }

        if (is_object($response)) {
            return json_encode((array)$response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return (string)$response;
    }
}
