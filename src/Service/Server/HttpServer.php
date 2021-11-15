<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\BindsProvider;
use Mini\Context;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\Support\Sendable;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Service\HttpMessage\Stream\SwooleStream;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Service\HttpServer\RouteService;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Server as HttpSwooleServer;
use Mini\Service\HttpMessage\Server\Request as Psr7Request;
use Mini\Service\HttpMessage\Server\Response as Psr7Response;
use Throwable;

class HttpServer extends AbstractServer
{
    /**
     * @var RouteService|null
     */
    protected ?RouteService $route = null;

    protected string $type = 'Http';

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode'], $this->config['sock_type']);
    }

    /**
     * @param HttpSwooleServer $server
     * @param int $workerId
     * @throws Throwable
     */
    public function onWorkerStart(HttpSwooleServer $server, int $workerId): void
    {
        parent::onWorkerStart($server, $workerId);
        try {
            $this->route = RouteService::getInstance();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @throws Throwable
     */
    public function onRequest(Request $request, Response $response): void
    {
        parent::onRequest($request, $response);
        try {
            $this->initRequestAndResponse($request, $response);
            if (!$this->route) {
                $this->route = RouteService::getInstance();
            }
            $resp = $this->route->dispatch($request);
            if (!isset($resp)) {
                return;
            }
            if (!$resp instanceof \Psr\Http\Message\ResponseInterface) {
                $resp = $this->transferToResponse($resp);
            }
            if (!$resp instanceof Sendable) {
                return;
            }
            $resp = $resp->withHeader('Server', 'Mini');
            /**
             * @var $resp Psr7Response
             */
            $resp = app('middleware')->bootAfterRequest($resp);
            if (request()->getMethod() === 'HEAD') {
                $resp->send(false);
            } else {
                $resp->send(true);
            }
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    /**
     * @throws Throwable
     */
    protected function initialProvider(): void
    {
        $map = BindsProvider::binds() + config('app.bind', []);
        $app = app();
        foreach ($map as $key => $value) {
            $app->bind($key, $value);
        }
    }

    /**
     * @param $request
     * @param $response
     * @throws Throwable
     */
    protected function initRequestAndResponse($request, $response): void
    {
        Context::set(RequestInterface::class, Psr7Request::loadFromSwooleRequest($request));
        Context::set(ResponseInterface::class, new Psr7Response($response));
        $this->initialProvider();
    }

    /**
     * @param $response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws Throwable
     */
    protected function transferToResponse($response): \Psr\Http\Message\ResponseInterface
    {
        if ($response instanceof Htmlable) {
            return $this->response()
                ->withAddedHeader('content-type', 'text/html;charset=UTF-8')
                ->withBody(new SwooleStream($response->toHtml()));
        }
        if (is_string($response)) {
            return $this->response()
                ->withAddedHeader('content-type', 'text/plain;charset=UTF-8')
                ->withBody(new SwooleStream($response));
        }

        if ($response instanceof Arrayable) {
            $response = $response->toArray();
        }

        if (is_array($response)) {
            return $this->response()
                ->withAddedHeader('content-type', 'application/json;charset=UTF-8')
                ->withBody(new SwooleStream(json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));
        }

        if ($response instanceof Jsonable) {
            return $this->response()
                ->withAddedHeader('content-type', 'application/json;charset=UTF-8')
                ->withBody(new SwooleStream((string)$response->toJson()));
        }

        if (is_object($response)) {
            return method_exists($response, '__toString')
                ? $this->response()
                    ->withAddedHeader('content-type', 'text/plain;charset=UTF-8')
                    ->withBody(new SwooleStream((string)$response))
                : $this->response()
                    ->withAddedHeader('content-type', 'application/json;charset=UTF-8')
                    ->withBody(new SwooleStream(json_encode((array)$response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)));
        }

        return $this->response()->withAddedHeader('content-type', 'text/plain;charset=UTF-8')->withBody(new SwooleStream((string)$response));
    }

    /**
     * @return mixed
     */
    public function response()
    {
        return Context::get(ResponseInterface::class);
    }

    /**
     * @return mixed
     */
    public function request()
    {
        return Context::get(RequestInterface::class);
    }
}
