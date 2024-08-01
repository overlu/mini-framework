<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\BindsProvider;
use Mini\Context;
use Mini\Contracts\Request as RequestInterface;
use Mini\Contracts\Response as ResponseInterface;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\Support\Jsonable;
use Mini\Contracts\Support\Sendable;
use Mini\Mail\Mailable;
use Mini\Service\HttpMessage\Server\Request as Psr7Request;
use Mini\Service\HttpMessage\Server\Response as Psr7Response;
use Mini\Service\HttpMessage\Stream\SwooleStream;
use Mini\Service\Route\Route;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class HttpServer extends AbstractServer
{
    protected string $type = 'http';

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode'], $this->config['sock_type']);
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
            /**
             * @var $route Route
             */
            $route = app('route');
            $this->initRequestAndResponse($request, $response);
            $resp = $route->dispatch($request);
            if (!isset($resp)) {
                $resp = '';
            }
            if (!$resp instanceof \Psr\Http\Message\ResponseInterface) {
                $resp = $this->transferToResponse($resp);
            }
            if (!$resp instanceof Sendable) {
                return;
            }
            $resp = $resp->withHeader('Server', 'Mini');
            app('middleware')
                ->bootAfterRequest($resp, $route->getController())
                ->send($request->getMethod() !== 'HEAD');
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
        if ($response instanceof Mailable) {
            return $this->response()
                ->withAddedHeader('content-type', 'text/html;charset=UTF-8')
                ->withBody(new SwooleStream($response->render()));
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
                ->withBody(new SwooleStream(json_encode($response, JSON_UNESCAPED_UNICODE)));
        }

        if ($response instanceof Jsonable) {
            return $this->response()
                ->withAddedHeader('content-type', 'application/json;charset=UTF-8')
                ->withBody(new SwooleStream((string)$response->toJson()));
        }

        if (class_exists(SymfonyResponse::class) && $response instanceof SymfonyResponse) {
            $res = $this->response()->withStatus($response->getStatusCode());
            foreach ($response->headers->all() as $name => $values) {
                foreach ($values as $value) {
                    $res = $res->withHeader($name, $value);
                }
            }
            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            ob_start();
            $response->sendContent();
            $content = ob_get_clean();
            return $res->withBody(new SwooleStream((string)$content));
        }

        if (is_object($response)) {
            return method_exists($response, '__toString')
                ? $this->response()
                    ->withAddedHeader('content-type', 'text/plain;charset=UTF-8')
                    ->withBody(new SwooleStream((string)$response))
                : $this->response()
                    ->withAddedHeader('content-type', 'application/json;charset=UTF-8')
                    ->withBody(new SwooleStream(json_encode((array)$response, JSON_UNESCAPED_UNICODE)));
        }

        return $this->response()->withAddedHeader('content-type', 'text/plain;charset=UTF-8')->withBody(new SwooleStream((string)$response));
    }

    /**
     */
    public function response(): \Psr\Http\Message\ResponseInterface
    {
        return Context::get(ResponseInterface::class);
    }

    /**
     * @return \Mini\Service\HttpServer\Request | \Mini\Service\HttpMessage\Server\Request
     */
    public function request()
    {
        return Context::get(RequestInterface::class);
    }
}
