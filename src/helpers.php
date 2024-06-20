<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

use Mini\Config\Config;
use Mini\Container\Container;
use Mini\Context;
use Mini\Contracts\HttpMessage\WebsocketRequestInterface;
use Mini\Contracts\HttpMessage\WebsocketResponseInterface;
use Mini\Contracts\Request as RequestInterface;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\Support\Jsonable;
use Mini\Contracts\View\View;
use Mini\Events\Dispatcher;
use Mini\Exception\WebsocketException;
use Mini\Server;
use Mini\Service\HttpMessage\Cookie\Cookie;
use Mini\Service\HttpMessage\Uri\Uri;
use Mini\Service\Route\UrlGenerator;
use Mini\Service\WsServer\Request;
use Mini\Service\WsServer\Response;
use Mini\Session\Session;
use Mini\Singleton;
use Mini\Support\ApplicationContext;
use Mini\Support\Arr;
use Mini\Support\Collection;
use Mini\Support\Coroutine;
use Mini\Support\Dotenv;
use Mini\Support\HigherOrderTapProxy;
use Mini\Support\HtmlString;
use Mini\Support\Parallel;
use Mini\Support\Str;
use Mini\Support\Waiter;
use Mini\View\Factory;
use Psr\Http\Message\ResponseInterface;
use Swoole\Runtime;

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function value(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('app')) {
    /**
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|Container
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }
        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param int $times
     * @param callable $callback
     * @param int $sleep
     * @param callable|null $when
     * @return mixed
     * @throws Exception
     */
    function retry(int $times, callable $callback, int $sleep = 0, ?callable $when = null): mixed
    {
        $attempts = 0;

        beginning:
        $attempts++;
        $times--;
        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($times < 1 || ($when && !$when($e))) {
                throw $e;
            }
            if ($sleep) {
                if (Coroutine::inCoroutine()) {
                    \Swoole\Coroutine::sleep((float)($sleep / 1000));
                } else {
                    usleep($sleep * 1000);
                }
            }
            goto beginning;
        }
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback.
     *
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed
     */
    function with(mixed $value, callable $callback = null): mixed
    {
        return is_null($callback) ? $value : $callback($value);
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     * @param mixed|null $value
     * @return Collection
     */
    function collect(mixed $value = null): Collection
    {
        return new Collection($value);
    }
}

if (!function_exists('data_fill')) {
    /**
     * Fill in data where it's missing.
     * @param mixed $target
     * @param array|string $key
     * @param mixed $value
     * @return array|object
     */
    function data_fill(mixed &$target, mixed $key, mixed $value): object|array
    {
        return data_set($target, $key, $value, false);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     * @param array|int|string $key
     * @param mixed|null $default
     * @param mixed $target
     * @return array|null|mixed
     */
    function data_get(mixed $target, mixed $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return value($default);
                }
                $result = [];
                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }
                return in_array('*', $key, true) ? Arr::collapse($result) : $result;
            }
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }
        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     * @param mixed $target
     * @param array|string $key
     * @param bool $overwrite
     * @param mixed $value
     * @return array|object
     */
    function data_set(mixed &$target, mixed $key, mixed $value, bool $overwrite = true): object|array
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        if (($segment = array_shift($segments)) === '*') {
            if (!Arr::accessible($target)) {
                $target = [];
            }
            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (!Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }
        return $target;
    }
}

if (!function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     * @param array $array
     * @return mixed
     */
    function head(array $array): mixed
    {
        return reset($array);
    }
}

if (!function_exists('last')) {
    /**
     * Get the last element from an array.
     * @param array $array
     * @return mixed
     */
    function last(array $array): mixed
    {
        return end($array);
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed|HigherOrderTapProxy
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }
        $callback($value);
        return $value;
    }
}

if (!function_exists('call')) {
    /**
     * Call a callback with the arguments.
     * @param mixed $callback
     * @param array $args
     * @return null|mixed
     */
    function call(mixed $callback, array $args = []): mixed
    {
        if ($callback instanceof \Closure) {
            $result = $callback(...$args);
        } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
            $result = $callback(...$args);
        } elseif (is_array($callback)) {
            [$object, $method] = $callback;
            $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
        } else {
            $result = call_user_func_array($callback, $args);
        }
        return $result;
    }
}

if (!function_exists('go')) {
    /**
     * @param callable $callable
     * @return int
     */
    function go(callable $callable): int
    {
        $id = Coroutine::create($callable);
        return max($id, 0);
    }
}

if (!function_exists('co')) {
    /**
     * @param callable $callable
     * @return int
     */
    function co(callable $callable): int
    {
        $id = Coroutine::create($callable);
        return max($id, 0);
    }
}

if (!function_exists('defer')) {
    /**
     * @param callable $callable
     */
    function defer(callable $callable): void
    {
        Coroutine::defer($callable);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     * @param object|string $class
     * @return string
     */
    function class_basename(object|string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     * @param object|string $trait
     * @return array
     */
    function trait_uses_recursive(object|string $trait): array
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     * @param object|string $class
     * @return array
     */
    function class_uses_recursive(object|string $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

if (!function_exists('setter')) {
    /**
     * Create a setter string.
     * @param string $property
     * @return string
     */
    function setter(string $property): string
    {
        return 'set' . Str::studly($property);
    }
}

if (!function_exists('getter')) {
    /**
     * Create a getter string.
     * @param string $property
     * @return string
     */
    function getter(string $property): string
    {
        return 'get' . Str::studly($property);
    }
}

if (!function_exists('parallel')) {
    /**
     * @param callable[] $callables
     * @return array
     */
    function parallel(array $callables): array
    {
        $parallel = new Parallel();
        foreach ($callables as $key => $callable) {
            $parallel->add($callable, $key);
        }
        return $parallel->wait();
    }
}

if (!function_exists('make')) {
    /**
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    function make(string $name, array $parameters = []): mixed
    {
        if (ApplicationContext::hasContainer()) {
            $container = ApplicationContext::getContainer();
            if (method_exists($container, 'make')) {
                return $container->make($name, $parameters);
            }
        }
        $parameters = array_values($parameters);
        return new $name(...$parameters);
//        return Container::getInstance()->make($name, $parameters);
    }
}

if (!function_exists('run')) {
    /**
     * Run callable in non-coroutine environment, all hook functions by Swoole only available in the callable.
     * @param callable $callback
     * @param int $flags
     * @return bool
     */
    function run(callable $callback, int $flags = SWOOLE_HOOK_ALL): bool
    {
        if (Coroutine::inCoroutine()) {
            throw new RuntimeException('Function \'run\' only execute in non-coroutine environment.');
        }
        Runtime::enableCoroutine(true, $flags);
        $result = \Swoole\Coroutine\Run($callback);
        Runtime::enableCoroutine(false);
        return $result;
    }
}

if (!function_exists('getInstance')) {
    /**
     * 获取实例
     * @param $class
     * @return mixed
     */
    function getInstance($class): mixed
    {
        return ($class)::getInstance();
    }
}

if (!function_exists('env')) {
    /**
     * get or set environment
     * @param null $key
     * @param null $default
     * @return Singleton|Dotenv|mixed|void|null
     */
    function env($key = null, $default = null)
    {
        if (is_null($key)) {
            return Dotenv::getInstance();
        }
        if (is_string($key)) {
            return Dotenv::getInstance()->get($key, $default);
        }
        if (is_array($key)) {
            Dotenv::getInstance()->setMany($key);
        }
    }
}

if (!function_exists('config')) {
    /**
     * 获取/设置配置数据
     *
     * 如果key为数组，则为设置配置数据
     *
     * @param array|string $key
     * @param mixed|null $default
     * @return mixed|void
     */
    function config(array|string $key, mixed $default = null)
    {
        $config = app()->has('config') ? app('config') : Config::getInstance();
        if (is_array($key)) {
            $config->set($key);
            return;
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('redis')) {
    /**
     * 获取redis实例
     * @param string $connection
     * @return Redis
     */
    function redis(string $connection = 'default'): Redis
    {
        return app('redis')->getConnection($connection);
    }
}

if (!function_exists('server')) {
    /**
     * 获取server
     * @return \Swoole\Server
     */
    function server(): \Swoole\Server
    {
        return Server::getInstance()->get();
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     *
     * @param array $args
     * @return array|null
     */
    function event(...$args): ?array
    {
        return app(Dispatcher::class)->dispatch(...$args);
    }
}

if (!function_exists('task')) {
    /**
     * Dispatch an event and call the listeners.
     *
     * @param array $args
     * @return array|null
     */
    function task(...$args)
    {
        return app(Dispatcher::class)->task(...$args);
    }
}

if (!function_exists('request')) {
    /**
     * 获取request资源
     * @param null $key
     * @param null $default
     * @return Mini\Service\HttpServer\Request | Mini\Service\HttpMessage\Server\Request
     */
    function request($key = null, $default = null)
    {
        if (!Context::has('IsInRequestEvent')) {
            throw new RuntimeException("Not In Request Environment.");
        }
        if (is_null($key)) {
            return app(RequestInterface::class);
        }

        if (is_array($key)) {
            return app(RequestInterface::class)->only($key);
        }
        $value = app(RequestInterface::class)->input($key);

        return is_null($value) ? value($default) : $value;
    }
}

if (!function_exists('ws_request')) {
    /**
     * 获取websocket request资源
     * @return Request
     */
    function ws_request(): Request
    {
        if (!Context::has('IsInWebsocketEvent')) {
            throw new RuntimeException("Not In Websocket Environment.");
        }
        return app(WebsocketRequestInterface::class);
    }
}

if (!function_exists('response')) {
    /**
     * 获取response资源
     * @return Mini\Service\HttpServer\Response | Mini\Service\HttpMessage\Server\Response
     */
    function response()
    {
        if (!Context::has('IsInRequestEvent')) {
            throw new RuntimeException("Not In Request Environment.");
        }
        return app(\Mini\Contracts\Response::class);
    }
}

if (!function_exists('ws_response')) {
    /**
     * 获取websocket response资源
     * @return Response
     */
    function ws_response(): Response
    {
        if (!Context::has('IsInWebsocketEvent')) {
            throw new RuntimeException("Not In Websocket Environment.");
        }
        return app(WebsocketResponseInterface::class);
    }
}

if (!function_exists('url')) {
    /**
     * 动态生成url
     * @param ?string $path
     * @param array $params
     * @param string $fragment
     * @return UrlGenerator|string
     */
    function url(?string $path = null, array $params = [], string $fragment = ''): UrlGenerator|string
    {
        if (is_null($path)) {
            return app('url');
        }
        return app('url')->make($path, $params, $fragment)->getUrl();
    }
}

if (!function_exists('html')) {
    /**
     * @param string $string
     * @return HtmlString
     */
    function html(string $string): HtmlString
    {
        return new HtmlString($string);
    }
}

if (!function_exists('base_path')) {
    /**
     * 获取目录
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param array|string|null $key
     * @param mixed|null $default
     * @return mixed|Session
     */
    function session(array|string $key = null, mixed $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (!function_exists('storage_path')) {
    /**
     * 获取仓库目录
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    /**
     * 获取配置目录
     * @param string $path
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'config' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('public_path')) {
    /**
     * 开放目录
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'public' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('stub_path')) {
    /**
     * 获取模板目录
     * @param string $path
     * @return string
     */
    function stub_path(string $path = ''): string
    {
        return resource_path('stubs/' . $path);
    }
}

if (!function_exists('resource_path')) {
    /**
     * 获取资源目录
     * @param string $path
     * @return string
     */
    function resource_path(string $path = ''): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the database path.
     *
     * @param string $path
     * @return string
     */
    function database_path(string $path = ''): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . 'database' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('upload_path')) {
    /**
     * 获取上传目录
     * @param string $dir
     * @return string
     */
    function upload_path(string $dir = ''): string
    {
        return storage_path('uploads/' . $dir);
    }
}

if (!function_exists('runtime_path')) {
    /**
     * 获取运行目录
     * @param string $dir
     * @return string
     */
    function runtime_path(string $dir = ''): string
    {
        return storage_path('runtime/' . $dir);
    }
}

if (!function_exists('array_plus')) {
    /**
     * 数组合并，键相同值相加
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    function array_plus(array $arr1, array $arr2): array
    {
        foreach ($arr1 as $key => $val) {
            if (isset($arr2[$key])) {
                $arr1[$key] += $arr2[$key];
            }
        }
        return array_merge($arr2, $arr1);
    }
}

if (!function_exists('abort')) {
    /**
     * @param int $code
     * @param string $message
     * @param array $headers
     */
    function abort(int $code, string $message = '', array $headers = []): void
    {
        throw new \Mini\Exception\HttpException($message, $code, $headers);
    }
}

if (!function_exists('ws_abort')) {
    /**
     * @param int $code
     * @param string $message
     */
    function ws_abort(int $code, string $message = ''): void
    {
        throw new WebsocketException($message, $code);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     * @param Htmlable|string|Arrayable|Jsonable $value
     * @param bool $doubleEncode
     * @return string
     */
    function e(mixed $value, bool $doubleEncode = false): string
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        if ($value instanceof Arrayable) {
            return json_encode($value->toArray(), JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof Jsonable) {
            return $value->toJson();
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string)$value
                : json_encode((array)$value, JSON_UNESCAPED_UNICODE);
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string|null $view
     * @param array $data
     * @return View
     */
    function view(?string $view = null, array $data = []): View
    {
        $factory = app('view');
        if (empty($view)) {
            return $factory;
        }

        return $factory->make($view, $data);
    }
}

if (!function_exists('is_json')) {
    /**
     * @param $string
     * @return bool
     */
    function is_json($string): bool
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('__')) {
    /**
     * translate，参数为空不会解析
     * @param string|null $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    function __(?string $id = null, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return app('translator')->get($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('trans')) {
    /**
     * translate，会解析参数
     * @param string|null $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    function trans(?string $id = null, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return app('translator')->trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('cookie')) {
    /**
     * @param string $name
     * @param $value
     * @param int $minutes
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return Cookie
     */
    function cookie(string $name, $value, int $minutes = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): Cookie
    {
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
        return new Mini\Service\HttpMessage\Cookie\Cookie($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
    }
}

if (!function_exists('redirect')) {
    /**
     * 跳转
     * @param string|Uri $toUrl
     * @param int $status
     * @param string $schema
     * @return ResponseInterface
     */
    function redirect(Uri|string $toUrl, int $status = 302, string $schema = 'http'): ResponseInterface
    {
        return response()->redirect($toUrl, $status, $schema);
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (!function_exists('wait')) {
    /**
     * @param Closure $closure
     * @param float|null $timeout
     * @return mixed
     */
    function wait(Closure $closure, ?float $timeout = null): mixed
    {
        return app()->get(Waiter::class)->wait($closure, $timeout);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field.
     *
     * @return HtmlString
     */
    function csrf_field(): HtmlString
    {
        return new HtmlString('<input type="hidden" name="_token" value="' . csrf_token() . '">');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     * @return string
     */
    function csrf_token(): string
    {
        $session = app('session');

        if (isset($session)) {
            return $session->token();
        }

        throw new RuntimeException('Application session store not set.');
    }
}


if (!function_exists('is_production_env')) {
    /**
     * @return bool
     */
    function is_production_env(): bool
    {
        return env('APP_ENV', 'local') === 'production';
    }
}

if (!function_exists('is_local_env')) {
    /**
     * @return bool
     */
    function is_local_env(): bool
    {
        return env('APP_ENV', 'local') === 'local';
    }
}

if (!function_exists('is_dev_env')) {
    /**
     * @param bool $include_local
     * @return bool
     */
    function is_dev_env(bool $include_local = false): bool
    {
        $app_env = strtolower(env('APP_ENV', 'local'));
        return in_array($app_env, $include_local ? ['local', 'dev', 'develop', 'test'] : ['dev', 'develop', 'test'], true);
    }
}

if (!function_exists('mini_version')) {
    /**
     * 获取mini框架版本
     * @return string
     */
    function mini_version(): string
    {
        return app()->version();
    }
}

if (!function_exists('throw_if')) {
    /**
     * Throw the given exception if the given condition is true.
     * @template TException of \Throwable
     *
     * @param mixed $condition
     * @param TException $exception
     * @param mixed ...$parameters
     * @return bool
     *
     * @throws TException
     */
    function throw_if(mixed $condition, mixed $exception = 'RuntimeException', ...$parameters): bool
    {
        if ($condition) {
            if (is_string($exception) && class_exists($exception)) {
                $exception = new $exception(...$parameters);
            }

            throw is_string($exception) ? new RuntimeException($exception) : $exception;
        }

        return (bool)$condition;
    }
}

if (!function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     * @template TException of \Throwable
     * @param mixed $condition
     * @param TException $exception
     * @param mixed ...$parameters
     * @return bool
     *
     * @throws TException
     */
    function throw_unless(mixed $condition, mixed $exception = 'RuntimeException', ...$parameters): bool
    {
        throw_if(!$condition, $exception, ...$parameters);

        return (bool)$condition;
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param mixed $value
     * @return bool
     */
    function blank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param mixed $value
     * @return bool
     */
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (! function_exists('str')) {
    /**
     * @param $string
     * @return \Mini\Support\Stringable|mixed
     */
    function str($string = null): mixed
    {
        if (func_num_args() === 0) {
            return new class
            {
                public function __call($method, $parameters)
                {
                    return Str::$method(...$parameters);
                }

                public function __toString()
                {
                    return '';
                }
            };
        }

        return Str::of($string);
    }
}