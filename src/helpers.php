<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

use Mini\Container\Container;
use Mini\Database\Redis\Pool;
use Mini\Di;
use Mini\Support\ApplicationContext;
use Mini\Support\Arr;
use Mini\Support\Collection;
use Mini\Support\Coroutine;
use Mini\Support\HigherOrderTapProxy;
use Mini\Support\Str;
use Swoole\Runtime;

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('app')) {
    /**
     * @param string|null $abstract
     * @param array $parameters
     * @return Container|Di|object|mixed
     */
    /*function app(string $abstract = '', array $parameters = [])
    {

    }*/
    function app(?string $abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Di::getInstance();
        }

        return Di::getInstance()->make($abstract, $parameters);
        /*if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);*/
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
     *
     * @throws \Exception
     */
    function retry($times, callable $callback, $sleep = 0, $when = null)
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
                usleep($sleep * 1000);
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
    function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     * @param null|mixed $value
     * @return Collection
     */
    function collect($value = null)
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
     * @return array|mixed
     */
    function data_fill(&$target, $key, $value)
    {
        return data_set($target, $key, $value, false);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     * @param array|int|string $key
     * @param null|mixed $default
     * @param mixed $target
     * @return array|mixed
     */
    function data_get($target, $key, $default = null)
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
     * @return array|mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
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
    function head($array)
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
    function last($array)
    {
        return end($array);
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     * @param null|callable $callback
     * @param mixed $value
     * @return HigherOrderTapProxy|mixed
     */
    function tap($value, $callback = null)
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
    function call($callback, array $args = [])
    {
        $result = null;
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
     * @return bool|int
     */
    function go(callable $callable)
    {
        $id = Coroutine::create($callable);
        return $id > 0 ? $id : false;
    }
}

if (!function_exists('co')) {
    /**
     * @param callable $callable
     * @return bool|int
     */
    function co(callable $callable)
    {
        $id = Coroutine::create($callable);
        return $id > 0 ? $id : false;
    }
}

if (!function_exists('defer')) {
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
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     * @param string $trait
     * @return array
     */
    function trait_uses_recursive($trait)
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
    function class_uses_recursive($class)
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

if (!function_exists('make')) {
    /**
     * Create a object instance, if the DI container exist in ApplicationContext,
     * then the object will be create by DI container via `make()` method, if not,
     * the object will create by `new` keyword.
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    function make(string $name, array $parameters = [])
    {
        if (ApplicationContext::hasContainer()) {
            $container = ApplicationContext::getContainer();
            if (method_exists($container, 'make')) {
                return $container->make($name, $parameters);
            }
        }
        $parameters = array_values($parameters);
        return new $name(...$parameters);
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
    function getInstance($class)
    {
        return ($class)::getInstance();
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     * @param string $key
     * @param null|mixed $default
     * @return array|bool|false|mixed|string|void
     */
    function env($key, $default = null)
    {
        return \Mini\Support\Dotenv::getInstance()->getValue($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * 获取配置
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    function config($name, $default = null)
    {
        return \Mini\Config::getInstance()->get($name, $default);
    }
}

if (!function_exists('redis')) {
    /**
     * 获取redis实例
     * @param array $config
     * @param string $connection
     * @return \Swoole\Coroutine\Redis | \Redis
     */
    function redis(array $config = [], $connection = 'default')
    {
        return Pool::getInstance($config ?: [])->getConnection($connection);
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
        return $arr1 + $arr2;
    }
}

if (!function_exists('request')) {
    /**
     * 获取request资源
     * @return Mini\Service\HttpServer\Request
     */
    function request()
    {
        return Di::getInstance()->make(Mini\Contracts\HttpMessage\RequestInterface::class);
    }
}

if (!function_exists('response')) {
    /**
     * 获取response资源
     * @return Mini\Service\HttpServer\Response | Mini\Service\HttpMessage\Server\Response
     */
    function response()
    {
        return Di::getInstance()->make(Mini\Contracts\HttpMessage\ResponseInterface::class);
    }
}

if (!function_exists('url')) {
    /**
     * 动态生成url
     * @param string $path
     * @param array $params
     * @param string $fragment
     * @return string
     */
    function url(string $path = '', array $params = [], string $fragment = ''): string
    {
        if ($request = request()) {
            $server = $request->getServerParams();
            $scheme = !empty($server['https']) && $server['https'] !== 'off' ? 'https' : 'http';
            $host = $request->header('host');
            $appUrl = rtrim($scheme . '://' . $host, '/');
        } else {
            $appUrl = rtrim(env('APP_URL'), '/');
        }
        $arr = parse_url($appUrl);
        $arr['query'] = !empty($params) ? http_build_query($params) : '';
        $arr['scheme'] = $arr['scheme'] ?? 'http';
        $arr['path'] = $path;
        $arr['fragment'] = $fragment;
        $arr['port'] = $arr['port'] ?? 80;
        return http_build_url($arr);
    }
}

if (!function_exists('http_build_url')) {
    /**
     * build url
     * @param array $urlArr
     * @return string
     */
    function http_build_url(array $urlArr): string
    {
        $url = $urlArr['scheme'] . '://' . $urlArr['host'];
        $url .= (!$urlArr['port'] || $urlArr['port'] === 80) ? '' : ':' . $urlArr['port'];
        $url .= $urlArr['path'] ? '/' . trim($urlArr['path'], '/') : '';
        $url .= $urlArr['query'] ? '?' . $urlArr['query'] : '';
        $url .= $urlArr['fragment'] ? '#' . $urlArr['fragment'] : '';
        return $url;
    }
}

if (!function_exists('sys_error_format')) {
    /**
     * 系统错误信息式化
     * @param $error_message
     * @param int $code
     * @return string
     */
    function sys_error_format($error_message, $code = 0)
    {
        $error_info = [
            'requestId' => \SeasLog::getRequestID(),
            'status' => [
                'success' => false,
                'message' => $error_message,
                'code' => $code,
            ],
            'method' => request()->getMethod(),
            'data' => []
        ];
        return json_encode($error_info, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
        return BASE_PATH . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
        return BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
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
        return BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
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
        return storage_path('stubs/' . $path);
    }
}

if (!function_exists('resource_path')) {
    /**
     * 获取资源目录
     * @param string $dir
     * @return string
     */
    function resource_path(string $dir = ''): string
    {
        return storage_path('resources/' . $dir);
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
        return $arr1 + $arr2;
    }
}

if (!function_exists('to404')) {
    /**
     * @return string|null
     * @throws \Mini\Exceptions\InvalidResponseException
     */
    function to404(): ?string
    {
        return toCode(404, 'Whoops, not found.');
    }
}

if (!function_exists('to405')) {
    /**
     * @return string|null
     * @throws \Mini\Exceptions\InvalidResponseException
     */
    function to405(): ?string
    {
        return toCode(405, 'Whoops, method not allowed.');
    }
}

if (!function_exists('toCode')) {
    /**
     * @param int $code
     * @param mixed $message
     * @return string|null
     * @throws \Mini\Exceptions\InvalidResponseException
     */
    function toCode(int $code, $message): ?string
    {
        if ($response = response()) {
            $response->withStatus($code)
                ->withAddedHeader('content-type', 'application/json;charset=UTF-8')
                ->withHeader('Server', 'Mini')
                ->raw(sys_error_format($message, $code))
                ->send(true);
            return '#%Mini::code%#';
        }
        return null;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     * @param \Mini\Contracts\Support\Htmlable|string $value
     * @param bool $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = false): string
    {
        if ($value instanceof \Mini\Contracts\Support\Htmlable) {
            return $value->toHtml();
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return \Mini\View\View|\Mini\Contracts\View\Factory
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        $factory = app('view');
        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}

if (!function_exists('is_json')) {
    /**
     * @param $string
     * @return bool
     */
    function is_json(string $string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}

if (!function_exists('debug')) {
    /**
     * @param $var
     */
    function debug($var, ...$moreVars)
    {
        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
        $output = fopen('php://memory', 'r+b');
        $dumper->dump($cloner->cloneVar($var), $output);
        foreach ($moreVars as $moreVar) {
            $dumper->dump($cloner->cloneVar($moreVar), $output);
        }
        $output = stream_get_contents($output, -1, 0);
        $swResponse = response()->getSwooleResponse();
        if ($swResponse) {
            $swResponse->header('content-type', 'text/html;charset=UTF-8', true);
            $swResponse->header('Server', 'Mini', true);
            $swResponse->write(new \Mini\Service\HttpMessage\Stream\SwooleStream($output));
        }
    }
}
