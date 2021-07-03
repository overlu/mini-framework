<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Exception;
use JsonException;
use Mini\Context;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Listener;
use Mini\Service\HttpMessage\Server\Request as Psr7Request;
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
        if ($this->handler) {
            if (!empty($this->handler['className'])) {
                $wsResponse = call([$this->handler['callable'], 'onMessage'], [$server, $frame, $this->handler['data']]);
                $wsResponse = method_exists($this->handler['callable'], 'afterDispatch') ? call([$this->handler['callable'], 'afterDispatch'], [$wsResponse, $frame, $this->handler['className'], $this->handler['data']]) : $wsResponse;
            } else {
                $wsResponse = call($this->handler['callable'], [$server, $frame, $this->handler['data']]);
            }
            if ($wsResponse) {
                app(ResponseInterface::class)->push($this->transferToWsResponse($wsResponse));
            }
        }
    }

    /**
     * @param $request
     * @param Server $server
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    protected function initWsRequestAndResponse($request, Server $server): void
    {
        Context::set(RequestInterface::class, Psr7Request::loadFromSwooleRequest($request));
        $app = app();
        $app->bind(RequestInterface::class, \Mini\Service\HttpServer\Request::class);
        $app->offsetSet(ResponseInterface::class, new Response($request, $server));
        Context::set('IsInRequestEvent', true);
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
                    $server->push($request->fd, $this->transferToWsResponse($dispatchResp));
                    $server->close($request->fd);
                    return;
                }
                $this->handler = [
                    'callable' => $resp['class'],
                    'data' => $resp['data'],
                    'className' => $resp['className']
                ];
                call([$resp['class'], 'onOpen'], [$server, $request, $this->handler['data']]);
                return;
            }
            if (is_array($resp) && isset($resp['callable'])) {
                $this->handler = $resp;
                return;
            }
            if (is_array($resp) && isset($resp['error'])) {
                $server->push($request->fd, $this->error($resp['error'], $resp['code'] ?? 0));
            }
            if (is_string($resp)) {
                $server->push($request->fd, $this->error($resp));
            }
            $server->close($request->fd);
        } catch (Throwable $throwable) {
            $server->close($request->fd);
            app('exception')->report($throwable);
        }
    }

    /**
     * @param $response
     * @return false|string
     * @throws JsonException
     */
    protected function transferToWsResponse($response)
    {
        if ($response instanceof Arrayable) {
            $response = $response->toArray();
        }

        if (is_array($response)) {
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if ($response instanceof Jsonable) {
            return (string)$response->toJson();
        }

        if (is_object($response)) {
            return method_exists($response, '__toString') ? (string)$response : json_encode((array)$response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return (string)$response;
    }

    /**
     * @param Server $server
     * @param $fd
     * @throws Throwable
     */
    public function onClose(Server $server, $fd)
    {
        if (!empty($this->handler['className'])) {
            $wsResponse = call([$this->handler['callable'], 'onClose'], [$server, $fd, $this->handler['data']]);
        }
        Listener::getInstance()->listen('close', $server, $fd);
    }
}