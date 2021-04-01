<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Exception;
use JsonException;
use Mini\BindsProvider;
use Mini\Context;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Listener;
use Mini\Service\HttpMessage\Server\Request as Psr7Request;
use Mini\Service\WsServer\Request;
use Mini\Service\WsServer\Response;
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
     * @param $frame
     * @throws Exception
     */
    public function onMessage(Server $server, $frame): void
    {
        if ($this->handler) {
            $wsResponse = $this->transferToWsResponse(call($this->handler['callable'], [$frame, $this->handler['data'], $server]));
            if (!empty($this->handler['className'])) {
                $wsResponse = method_exists($this->handler['callable'][0], 'afterDispatch') ? $this->handler['callable'][0]->afterDispatch($wsResponse, $this->handler['callable'][1], $this->handler['className']) : $wsResponse;
            }
            app(ResponseInterface::class)->push($wsResponse);
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
    public function onOpen(Server $server, $request): void
    {
        try {
            $this->initWsRequestAndResponse($request, $server);
            $resp = $this->route->dispatchWs($request);

            if (is_array($resp) && isset($resp['class'])) {
                $controller = new $resp['class']($resp['method']);
                if (method_exists($controller, 'beforeDispatch') && $dispatchResp = $controller->beforeDispatch($resp['method'], $resp['class'])) {
                    $server->push($request->fd, $this->transferToWsResponse($dispatchResp));
                    $server->close($request->fd);
                    return;
                }
                $this->handler = [
                    'callable' => [$controller, $resp['method']],
                    'data' => $resp['data'],
                    'className' => $resp['class']
                ];
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
        Listener::getInstance()->listen('close', $server, $fd);
    }
}