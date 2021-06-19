<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpServer;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Mini\BindsProvider;
use Mini\Contracts\HttpMessage\WebsocketControllerInterface;
use Mini\Exception\HttpException\MethodNotAllowedHttpException;
use Mini\Exception\HttpException\NotFoundHttpException;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use RuntimeException;
use Swoole\Http\Request;
use Throwable;
use function FastRoute\cachedDispatcher;

class RouteService
{
    private static RouteService $instance;

    private static $routes = [];

    private static bool $cached = false;

    /**
     * @var Dispatcher
     */
    private static Dispatcher $httpDispatcher;
    private static Dispatcher $wsDispatcher;

    private function __construct()
    {
        static::$cached = !config('app.route_cached', true);
        self::$routes = config('routes', []);
    }

    /**
     * @param array $route
     */
    public static function registerHttpRoute(array $route): void
    {
        self::$routes['http'][] = $route;
    }

    /**
     * @param array $route
     */
    public static function registerWsRoute(array $route): void
    {
        self::$routes['ws'][] = $route;
    }

    private static function parasMethod($method): array
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
    private static function parseHttpRoutes($httpRoutes, RouteCollector $routerCollector, array $namespace = []): void
    {
        foreach ($httpRoutes as $group => $route) {
            if (is_string($group) && is_array($route[0])) {
                $explodeGroup = explode('#', $group, 2);
                if (isset($explodeGroup[1])) {
                    $namespace[] = $explodeGroup[1];
                }
                $routerCollector->addGroup(trim($explodeGroup[0], '/'), static function (RouteCollector $routerCollector) use ($route, $namespace) {
                    self::parseHttpRoutes($route, $routerCollector, $namespace);
                });
                if (isset($explodeGroup[1])) {
                    array_pop($namespace);
                }
            } else {
                $namespaceString = !empty($namespace) ? implode('\\', $namespace) . '\\' : '';
                $handle = is_string($route[2]) ? $namespaceString . $route[2] : $route[2];
                $routerCollector->addRoute(static::parasMethod($route[0]), trim($route[1], '/'), $handle);
            }
        }
    }

    /**
     * @param $wsRoutes
     * @param RouteCollector $routerCollector
     * @param array $namespace
     */
    private static function parseWebSocketRoutes($wsRoutes, RouteCollector $routerCollector, array $namespace = []): void
    {
        foreach ($wsRoutes as $group => $route) {
            if (is_string($group) && is_array($route[0])) {
                $explodeGroup = explode('#', $group, 2);
                if (isset($explodeGroup[1])) {
                    $namespace[] = $explodeGroup[1];
                }
                $routerCollector->addGroup('/' . ltrim($group, '/'), static function (RouteCollector $routerCollector) use ($route, $namespace) {
                    self::parseWebSocketRoutes($route, $routerCollector, $namespace);
                });
                if (isset($explodeGroup[1])) {
                    array_pop($namespace);
                }
            } else {
                $namespaceString = !empty($namespace) ? implode('\\', $namespace) . '\\' : '';
                $handle = is_string($route[1]) ? $namespaceString . $route[1] : $route[1];
                $routerCollector->addRoute('GET', '/' . trim($route[0], '/'), $handle);
            }
        }
    }

    public static function getInstance(): RouteService
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$httpDispatcher = cachedDispatcher(
                static function (RouteCollector $routerCollector) {
                    self::parseHttpRoutes(isset(self::$routes['ws']) ? (self::$routes['http'] ?? []) : self::$routes, $routerCollector);
                },
                [
                    'cacheFile' => BASE_PATH . '/storage/app/http.route.cache', /* required 缓存文件路径，必须设置 */
                    'cacheDisabled' => static::$cached,     /* optional, enabled by default 是否缓存，可选参数，默认情况下开启 */
                ]
            );
            self::$wsDispatcher = cachedDispatcher(
                static function (RouteCollector $routerCollector) {
                    self::parseWebSocketRoutes(self::$routes['ws'] ?? [], $routerCollector);
                },
                [
                    'cacheFile' => BASE_PATH . '/storage/app/ws.route.cache', /* required 缓存文件路径，必须设置 */
                    'cacheDisabled' => static::$cached,     /* optional, enabled by default 是否缓存，可选参数，默认情况下开启 */
                ]
            );
        }
        return self::$instance;
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ReflectionException
     * @throws Throwable
     */
    public function dispatch(Request $request)
    {
        $method = $request->server['request_method'] ?? 'GET';
        $uri = $request->server['request_uri'] ?? '/';
        $routeInfo = self::$httpDispatcher->dispatch($method, rtrim($uri, '/') ?: '/');
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return $this->defaultRouter();
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException();
                break;
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
    protected function dispatchHandle($handler, array $params = [], string $uri = '')
    {
        if (is_string($handler)) {
            $handler = explode('@', $handler);
            if (count($handler) !== 2) {
                throw new RuntimeException("Router {$uri} Config Error, Only @ Are Supported");
            }
            $className = '\\App\\Controllers\\Http\\' . $handler[0];
            $func = $handler[1];
            if (!class_exists($className)) {
                throw new RuntimeException("Router {$uri} Defined Class {$className} Not Found");
            }
            $resp = app('middleware')->registerBeforeRequest($func, $className);
            if (!is_null($resp)) {
                return $resp;
            }
            $controller = new $className($func);
            if (!method_exists($controller, $func)) {
                throw new RuntimeException("Router {$uri} Defined {$className}->{$func} Method Not Found");
            }
            $method = (new ReflectionMethod($controller, $func));
            $data = $this->initialParams($method, $params);
            if (method_exists($controller, 'beforeDispatch') && $resp = $controller->beforeDispatch($func, $className)) {
                return $resp;
            }
            $resp = $method->invokeArgs($controller, $data);
            return method_exists($controller, 'afterDispatch') ? $controller->afterDispatch($resp, $func, $className) : $resp;
        }
        if (is_callable($handler)) {
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
        $uri = $request->server['request_uri'] ?? '/';
        $routeInfo = self::$wsDispatcher->dispatch('GET', rtrim($uri, '/'));
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return ['error' => 'method not found.', 'code' => 404];
            case Dispatcher::METHOD_NOT_ALLOWED:
                return ['error' => 'method not allowed, please change method to GET', 'code' => 405];
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                if (is_string($handler)) {
                    $className = '\\App\\Controllers\\Websocket\\' . $handler;
                    if (!class_exists($className)) {
                        throw new RuntimeException("Router {$uri} Defined Class {$className} Not Found");
                    }
                    $class = new $className;
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
                return ['error' => 'method not found.', 'code' => 404];
        }
        return ['error' => 'method not found.', 'code' => 404];
    }

    /**
     * @param $method
     * @param $vars
     * @return array
     * @throws Throwable
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
        if (isset(self::$routes['default'])) {
            return $this->dispatchHandle(self::$routes['default'], []);
        }
        throw new NotFoundHttpException();
    }
}