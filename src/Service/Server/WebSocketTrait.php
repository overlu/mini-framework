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
        try {
            Context::set('IsInWebsocketEvent', true);
            if ($this->handler) {
                if (!empty($this->handler['className'])) {
                    $wsResponse = call([$this->handler['callable'], 'onMessage'], [$server, $frame, $this->handler['data']]);
                    $wsResponse = method_exists($this->handler['callable'], 'afterDispatch') ? call([$this->handler['callable'], 'afterDispatch'], [$wsResponse, $frame, $this->handler['className'], $this->handler['data']]) : $wsResponse;
                } else {
                    $wsResponse = call($this->handler['callable'], [$server, $frame, $this->handler['data']]);
                }
                if ($wsResponse) {
                    ws_response()->push($wsResponse);
                }
            }
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    /**
     * @param $request
     * @param Server $server
     * @throws BindingResolutionException
     */
    protected function initWsRequestAndResponse(\Swoole\Http\Request $request, Server $server): void
    {
        Context::set('IsInWebsocketEvent', true);
        $app = app();
        $app->offsetSet(WebsocketRequestInterface::class, new Request($request, $server));
        $app->offsetSet(WebsocketResponseInterface::class, new Response($request, $server));
    }

    /**
     * @param Server $server
     * @param $request
     * @throws Throwable
     */
    public function onOpen(Server $server, \Swoole\Http\Request $request): void
    {
        try {
            $this->initWsRequestAndResponse($request, $server);
            $resp = $this->route->dispatchWs($request);
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
                if ($openRes = call([$resp['class'], 'onOpen'], [$server, $request, $this->handler['data']])) {
                    ws_response()->push($openRes);
                }
                return;
            }
            if (is_array($resp) && isset($resp['callable'])) {
                $this->handler = $resp;
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
     * @param $fd
     * @throws Throwable
     */
    public function onClose(Server $server, $fd)
    {
        if (!empty($this->handler['className'])) {
            call([$this->handler['callable'], 'onClose'], [$server, $fd, $this->handler['data']]);
        }
        Listener::getInstance()->listen('close', $server, $fd);
    }
}