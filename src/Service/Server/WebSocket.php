<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Contracts\Support\Sendable;
use Mini\Exceptions\Handler;
use Mini\Provider\BaseRequestService;
use Swoole\WebSocket\Server;

class WebSocket extends HttpServer
{
    protected string $type = 'WebSocket';

    /**
     * @var array|callable
     */
    private $handler;

    public function initialize(): void
    {
        $this->config = config('servers.ws');
        $this->worker_num = $this->config['settings']['worker_num'] ?? 1;
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'OnMessage']);
        \Mini\Server::getInstance()->set(self::class, $this->server);
    }

    public function onOpen(Server $server, $request)
    {
        try {
            $resp = $this->route->dispatchWs($request);
            if (is_callable($resp)) {
                $this->handler = $resp;
                return;
            }
            if (is_array($resp) && isset($resp['class'])) {
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
        if ($this->han)
            $server->push($frame->fd, json_encode($this->handler));
    }
}
