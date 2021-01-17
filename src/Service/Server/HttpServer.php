<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Config;
use Mini\BindsProvider;
use Mini\Context;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\Support\Sendable;
use Mini\Di;
use Mini\Exceptions\Handler;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Provider\BaseRequestService;
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
     * @var RouteService
     */
    protected RouteService $route;

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
        try {
            $this->route = RouteService::getInstance();
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
        parent::onWorkerStart($server, $workerId);
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
            [$psr7Request, $psr7Response] = $this->initRequestAndResponse($request, $response);
            BaseRequestService::getInstance()->before();
            $resp = $this->route->dispatch($request);
            if (!$resp instanceof \Psr\Http\Message\ResponseInterface) {
                $resp = $this->transferToResponse($resp);
            }
            if (!isset($resp) || !$resp instanceof Sendable) {
                return;
            }
            $resp = $resp->withHeader('Server', 'Mini');
            $resp = BaseRequestService::getInstance()->after($resp);
            if ($psr7Request->getMethod() === 'HEAD') {
                $resp->send(false);
            } else {
                $resp->send(true);
            }
        } catch (Throwable $throwable) {
            Context::destroy('IsInRequestEvent');
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
     * @return array
     * @throws Throwable
     */
    protected function initRequestAndResponse($request, $response): array
    {
        Context::set(RequestInterface::class, $psr7Request = Psr7Request::loadFromSwooleRequest($request));
        Context::set(ResponseInterface::class, $psr7Response = new Psr7Response($response));
        $this->initialProvider();
        return [$psr7Request, $psr7Response];
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
}
