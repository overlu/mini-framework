<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Route;

use ArrayAccess;
use Mini\Contracts\Container\BindingResolutionException;
use MiniRoute\Dispatcher;
use MiniRoute\RouteCollector;
use Mini\BindsProvider;
use Mini\Contracts\HttpMessage\WebsocketControllerInterface;
use Mini\Exception\HttpException\MethodNotAllowedHttpException;
use Mini\Exception\HttpException\NotFoundHttpException;
use Mini\Support\Arr;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use RuntimeException;
use Swoole\Http\Request;
use Throwable;
use function MiniRoute\simpleDispatcher;

class Route
{
    private array $routes = [];

    /**
     * @var Dispatcher
     */
    private Dispatcher $httpDispatcher;
    private Dispatcher $wsDispatcher;

    private ?object $controller = null;

    public function __construct()
    {
        $routes = config('routes', []);
        $this->routes['http'] = $routes['http'] ?? [];
        $this->routes['ws'] = $routes['ws'] ?? [];
        $this->routes['default'] = $routes['default'] ?? null;
    }

    /**
     * @param array $route
     */
    public function registerHttpRoute(array $route): void
    {
        $this->routes['http'][] = $route;
        $this->initRoutes();
    }

    /**
     * @param array $route
     */
    public function registerWsRoute(array $route): void
    {
        $this->routes['ws'][] = $route;
        $this->initRoutes();
    }

    /**
     * initial routes
     */
    public function initRoutes(): void
    {
        $this->httpDispatcher = simpleDispatcher(
            function (RouteCollector $routerCollector) {
                $this->parseHttpRoutes(isset($this->routes['ws']) ? ($this->routes['http'] ?? []) : $this->routes, $routerCollector);
            }
        );
        $this->wsDispatcher = simpleDispatcher(
            function (RouteCollector $routerCollector) {
                $this->parseWebSocketRoutes($this->routes['ws'], $routerCollector);
            }
        );
    }

    private function parasMethod($method): array
    {
        return strtoupper($method) === 'ANY' ? ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'PATCH', 'OPTIONS', 'LOCK', 'UNLOCK', 'PROPFIND', 'PURGE']
            : array_map(static function ($method) {
                return strtoupper($method);
            }, (array)$method);
    }

    /**
     * @param $httpRoutes
     * @param RouteCollector $routerCollector
     * @param array $namespace
     */
    private function parseHttpRoutes($httpRoutes, RouteCollector $routerCollector, array $namespace = []): void
    {
        foreach ($httpRoutes as $group => $route) {
            if (!is_array($route)) {
                continue;
            }
            if (is_string($group)) {
                $explodeGroup = explode('#', $group, 2);
                if (isset($explodeGroup[1])) {
                    $namespace[] = $explodeGroup[1];
                }
                $routerCollector->addGroup(trim($explodeGroup[0], '/'), function (RouteCollector $routerCollector) use ($route, $namespace) {
                    $this->parseHttpRoutes($route, $routerCollector, $namespace);
                });
                if (isset($explodeGroup[1])) {
                    array_pop($namespace);
                }
            } else if (isset($route[0]) && is_string($route[0])) {
                $namespaceString = !empty($namespace) ? implode('\\', $namespace) . '\\' : '';
                $handle = is_string($route[2]) ? $namespaceString . $route[2] : $route[2];
                $routerCollector->addRoute($this->parasMethod($route[0]), trim($route[1], '/'), $handle);
            }
        }
    }

    /**
     * @param $wsRoutes
     * @param RouteCollector $routerCollector
     * @param array $namespace
     */
    private function parseWebSocketRoutes($wsRoutes, RouteCollector $routerCollector, array $namespace = []): void
    {
        foreach ($wsRoutes as $group => $route) {
            if (!is_array($route)) {
                continue;
            }
            if (is_string($group)) {
                $explodeGroup = explode('#', $group, 2);
                if (isset($explodeGroup[1])) {
                    $namespace[] = $explodeGroup[1];
                }
                $routerCollector->addGroup(trim($explodeGroup[0], '/'), function (RouteCollector $routerCollector) use ($route, $namespace) {
                    $this->parseWebSocketRoutes($route, $routerCollector, $namespace);
                });
                if (isset($explodeGroup[1])) {
                    array_pop($namespace);
                }
            } else if (isset($route[0]) && is_string($route[0])) {
                $namespaceString = !empty($namespace) ? implode('\\', $namespace) . '\\' : '';
                $handle = is_string($route[1]) ? $namespaceString . $route[1] : $route[1];
                $routerCollector->addRoute('GET', trim($route[0], '/'), $handle);
            }
        }
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function dispatch(Request $request)
    {
        $this->controller = null;
        $method = $request->server['request_method'] ?? 'GET';
        $uri = $request->server['request_uri'] ?? '/';
        $routeInfo = $this->httpDispatcher->dispatch($method, rtrim($uri, '/') ?: '/');
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return $this->defaultRouter();
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException();
            case Dispatcher::FOUND:
                $request->routes = $routeInfo[2] ?? [];
                return $this->dispatchHandle($routeInfo[1], $request->routes, $uri);
        }
        return $this->defaultRouter();
    }

    /**
     * @param $handler
     * @param array $params
     * @param string $uri
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function dispatchHandle($handler, array $params = [], string $uri = '')
    {
        if (is_string($handler)) {
            $handler = explode('@', $handler);
            if (count($handler) !== 2) {
                throw new RuntimeException("Router {$uri} Config Error, Only @ Are Supported");
            }
            if (class_exists($handler[0])) {
                $className = $handler[0];
            } else {
                $className = '\\App\\Controllers\\Http\\' . $handler[0];
                if (!class_exists($className)) {
                    throw new RuntimeException("Router {$uri} Defined Class {$className} Not Found");
                }
            }
            $func = $handler[1];
            $this->controller = new $className($func, $params);
            $resp = app('middleware')->registerBeforeRequest($func, $this->controller);
            if (!is_null($resp)) {
                return $resp;
            }
            if (!method_exists($this->controller, $func)) {
                throw new RuntimeException("Router {$uri} Defined {$className}->{$func} Method Not Found");
            }
            $method = (new ReflectionMethod($this->controller, $func));
            $data = $this->initialParams($method, $params);
            if (method_exists($this->controller, 'beforeDispatch') && $resp = $this->controller->beforeDispatch($func, $className, $params)) {
                return $resp;
            }
            $resp = $method->invokeArgs($this->controller, $data);
            return method_exists($this->controller, 'afterDispatch') ? $this->controller->afterDispatch($resp, $func, $className, $params) : $resp;
        }
        if (is_callable($handler)) {
            $resp = app('middleware')->registerBeforeRequest(null, null);
            if (!is_null($resp)) {
                return $resp;
            }
            $data = $this->initialParams(new ReflectionFunction($handler), $params);
            return call_user_func_array($handler, $data);
        }
        throw new RuntimeException('Illegal default url format, only support controllerClass@method or callable');
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws Throwable
     */
    public function dispatchWs(Request $request): array
    {
        $this->controller = null;
        $uri = $request->server['request_uri'] ?? '/';
        $routeInfo = $this->wsDispatcher->dispatch('GET', rtrim($uri, '/') ?: '/');
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                ws_abort(404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
//                return ['error' => 'method not allowed, please change method to GET', 'code' => 405];
                ws_abort(405);
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                if (is_string($handler)) {
                    if (class_exists($handler)) {
                        $className = $handler;
                    } else {
                        $className = '\\App\\Controllers\\Websocket\\' . $handler;
                        if (!class_exists($className)) {
                            throw new RuntimeException("Router {$uri} Defined Class {$className} Not Found");
                        }
                    }
                    $class = new $className($routeInfo[2]);
                    if (!$class instanceof WebsocketControllerInterface) {
                        throw new RuntimeException("Class {$className} Should Instanceof " . WebsocketControllerInterface::class);
                    }
                    return [
                        'class' => $class,
                        'className' => $className,
                        'data' => $routeInfo[2]
                    ];
                }
                if (is_callable($handler)) {
                    return [
                        'callable' => $handler,
                        'data' => $routeInfo[2]
                    ];
                }
                ws_abort(404);
                break;
        }
        ws_abort(404);
        return [];
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @param $vars
     * @return array
     * @throws ReflectionException
     * @throws Throwable
     * @throws BindingResolutionException
     */
    protected function initialParams(ReflectionFunctionAbstract $method, $vars): array
    {
        $params = $method->getParameters();
        $data = [];
        foreach ($params as $param) {
            $name = $param->getName();
            if ($type = $param->getType()) {
                $key = $type->getName();
                if (class_exists($key) && !$obj = app()->make($key)) {
                    $obj = $this->getConfigProvider($key);
                }
                $data[$name] = $obj;
            } else {
                $data[$name] = $vars[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
            }
        }
        return $data;
    }

    /**
     * @param $key
     * @return mixed|null
     * @throws Throwable
     */
    protected function getConfigProvider($key)
    {
        $map = BindsProvider::binds() + config('app.bind', []);
        $value = $map[$key] ?? $key;
        app()->bind($key, $value);
        return app()->make($key);
    }

    /**
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function defaultRouter()
    {
        if (empty($this->routes['default'])) {
            throw new NotFoundHttpException();
        }
        return $this->dispatchHandle($this->routes['default'], []);
    }

    /**
     * 是否存在路由
     * @param $key
     * @return bool
     */
    public function hasRoute(string $key): bool
    {
        return Arr::has($this->routes, $key);
    }

    /**
     * 所有的路由
     * @return array
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * 所有的http路由
     * @return array
     */
    public function httpRoutes(): array
    {
        return $this->routes['http'] ?? [];
    }

    /**
     * 所有的ws路由
     * @return array
     */
    public function wsRoutes(): array
    {
        return $this->routes['ws'] ?? [];
    }

    /**
     * 获取路由
     * @param string $key
     * @param null $default
     * @return array|ArrayAccess|mixed
     */
    public function route(string $key, $default = null)
    {
        return Arr::get($this->routes, $key, $default);
    }

    /**
     * 获取http路由
     * @param string $key
     * @param null $default
     * @return array|ArrayAccess|mixed
     */
    public function httpRoute(string $key, $default = null)
    {
        return Arr::get($this->httpRoutes(), $key, $default);
    }

    /**
     * 获取ws路由
     * @param string $key
     * @param null $default
     * @return array|ArrayAccess|mixed
     */
    public function wsRoute(string $key, $default = null)
    {
        return Arr::get($this->wsRoutes(), $key, $default);
    }

    /**
     * 获取控制器
     * @return object|null
     */
    public function getController(): ?object
    {
        return $this->controller;
    }
}