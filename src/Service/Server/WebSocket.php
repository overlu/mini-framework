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
use Mini\Contracts\Support\Sendable;
use Mini\Exceptions\Handler;
use Mini\Provider\BaseRequestService;
use Mini\Service\HttpMessage\Server\Request as Psr7Request;
use Mini\Service\WsServer\Request;
use Mini\Service\WsServer\Response;
use Swoole\WebSocket\Server;

class WebSocket extends HttpServer
{
    protected string $type = 'WebSocket';

    /**
     * @var array|callable
     */
    private $handler = null;

    public function initialize(): void
    {
        $this->config = config('servers.ws');
        $this->worker_num = $this->config['settings']['worker_num'] ?? 1;
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
        \Mini\Server::getInstance()->set(self::class, $this->server);
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
            response()->push(call($this->handler['callable'], $this->handler['data']));
        }
    }

    protected function initWsRequestAndResponse($request, Server $server)
    {
        app()->offsetSet(RequestInterface::class, new Request($request, $server));
        app()->offsetSet(ResponseInterface::class, new Response($request, $server));
//        $this->initialProvider();
    }
}
