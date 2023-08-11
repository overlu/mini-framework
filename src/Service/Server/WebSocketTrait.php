<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Exception;
use Mini\Context;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\HttpMessage\WebsocketRequestInterface;
use Mini\Contracts\HttpMessage\WebsocketResponseInterface;
use Mini\Listener;
use Mini\Service\WsServer\Request;
use Mini\Service\WsServer\Response;
use Mini\Service\WsServer\User;
use ReflectionException;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Throwable;

/**
 * Trait WebSocketTrait
 * @package Mini\Service\Server
 */
trait WebSocketTrait
{

    /**
     * @var array|callable
     */
    private $handler;

    /**
     * @param Server $server
     * @param Frame $frame
     * @throws Exception
     */
    public function onMessage(Server $server, Frame $frame): void
    {
        parent::onMessage($server, $frame);
        try {
            if ($this->handler) {
                if (!empty($this->handler['className'])) {
                    $wsResponse = call([$this->handler['callable'], 'onMessage'], [$server, $frame, $this->handler['data']]);
                    $wsResponse = method_exists($this->handler['callable'], 'afterDispatch') ? call([$this->handler['callable'], 'afterDispatch'], [$wsResponse, $frame, $this->handler['className'], $this->handler['data']]) : $wsResponse;
                } else {
                    $wsResponse = call($this->handler['callable'], [
                        'onMessage',
                        [
                            'server' => $server,
                            'frame' => $frame,
                            'routeData' => $this->handler['data']
                        ]
                    ]);
                }
                if ($wsResponse) {
                    ws_response()->push($wsResponse);
                }
            }
        } catch (Throwable $throwable) {
            $server->close($frame->fd);
            app('exception')->throw($throwable);
        }
    }

    /**
     * @param \Swoole\Http\Request $request
     * @param Server $server
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function initWsRequestAndResponse(\Swoole\Http\Request $request, Server $server): void
    {
        $app = app();
        $app->offsetSet(WebsocketRequestInterface::class, new Request($request, $server));
        $app->offsetSet(WebsocketResponseInterface::class, new Response($request, $server));
    }

    /**
     * @param Server $server
     * @param \Swoole\Http\Request $request
     */
    public function onOpen(Server $server, \Swoole\Http\Request $request): void
    {
        try {
            parent::onOpen($server, $request);
            $this->initWsRequestAndResponse($request, $server);
            $resp = app('route')->dispatchWs($request);
            if (is_array($resp) && isset($resp['class'])) {
                if (method_exists($resp['class'], 'beforeDispatch') && $dispatchResp = $resp['class']->beforeDispatch($resp['className'], $resp['data'])) {
                    ws_response()->push($dispatchResp)->close();
                    return;
                }
                $this->handler = [
                    'callable' => $resp['class'],
                    'data' => $resp['data'],
                    'className' => $resp['className']
                ];
                if ($openRes = call([$resp['class'], 'onOpen'], [$server, $request, $resp['data']])) {
                    ws_response()->push($openRes);
                }
                return;
            }
            if (is_array($resp) && isset($resp['callable'])) {
                $this->handler = $resp;
                if ($openRes = call($resp['callable'], [
                    'onOpen',
                    [
                        'server' => $server,
                        'request' => $request,
                        'routeData' => $resp['data']
                    ]
                ])) {
                    ws_response()->push($openRes);
                }
                return;
            }
            if (is_array($resp) && isset($resp['error'])) {
                ws_response()->push($this->error($resp['error'], $resp['code'] ?? 0))->close();
                return;
            }
            if (is_string($resp)) {
                ws_response()->push($this->error($resp))->close();
                return;
            }
            ws_response()->push($this->error('whoops, something error'))->close();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    /**
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        if ($server->isEstablished($fd)) {
            try {
                Context::destroy('IsInWebsocketEvent');
                Listener::getInstance()->listen('close', $server, $fd);
                /**
                 * 解绑fd
                 */
                $this->unbindFd($fd);

                if (!empty($this->handler['className'])) {
                    call([$this->handler['callable'], 'onClose'], [$server, $fd, $this->handler['data'], $reactorId]);
                } elseif (!empty($this->handler['callable'])) {
                    call($this->handler['callable'], [
                        'onClose',
                        [
                            'server' => $server,
                            'fd' => $fd,
                            'routeData' => $this->handler['data'],
                            'reactorId' => $reactorId
                        ]
                    ]);
                }
                return;
            } catch (Throwable $throwable) {
                app('exception')->throw($throwable);
            }
        }
    }

    /**
     * 解绑fd
     * @param int $fd
     */
    private function unbindFd(int $fd): void
    {
        $uids = User::getUserByFd($fd);
        foreach ($uids as $uid) {
            User::unbind($uid, $fd);
        }
    }
}
