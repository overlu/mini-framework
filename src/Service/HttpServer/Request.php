<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpServer;

use Mini\Service\HttpMessage\Upload\UploadedFile;
use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Session\Session;
use Mini\Support\Arr;
use Mini\Context;
use Mini\Support\Str;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use SplFileInfo;
use stdClass;

/**
 * @property string $pathInfo
 * @property string $requestUri
 */
class Request implements RequestInterface
{
    /**
     * @var array the keys to identify the data of request in coroutine context
     */
    protected array $contextkeys = [
        'parsedData' => 'http.request.parsedData',
    ];

    public function __get($name)
    {
        return $this->getRequestProperty($name);
    }

    public function __set($name, $value)
    {
        return $this->storeRequestProperty($name, $value);
    }

    /**
     * Retrieve the data from query parameters, if $key is null, will return all query parameters.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return array|mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->getQueryParams();
        }
        return data_get($this->getQueryParams(), $key, $default);
    }

    /**
     * @param string|null $key
     * @param mixed $default
     * @return array|mixed
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        return $this->query($key, $default);
    }

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * @param array|mixed $keys
     * @return array
     */
    public function only(array $keys): array
    {
        $results = [];

        $input = $this->all();

        $placeholder = new stdClass();

        foreach ($keys as $key) {
            $value = data_get($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Retrieve the data from parsed body, if $key is null, will return all parsed body.
     *
     * @param string|null $key
     * @param mixed $default
     * @return array|mixed|object|null
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->getParsedBody();
        }
        return data_get($this->getParsedBody(), $key, $default);
    }

    /**
     * Retrieve the input data from request, include query parameters, parsed body and json body,
     * if $key is null, will return all the parameters.
     *
     * @param string $key
     * @param mixed $default
     * @return array|mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        $data = $this->getInputData();

        return data_get($data, $key, $default);
    }

    /**
     * Retrieve the input data from request via multi keys, include query parameters, parsed body and json body.
     *
     * @param array $keys
     * @param mixed $default
     * @return array
     */
    public function inputs(array $keys, mixed $default = null): array
    {
        $data = $this->getInputData();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = data_get($data, $key, $default[$key] ?? null);
        }

        return $result;
    }

    /**
     * Retrieve all input data from request, include query parameters, parsed body and json body.
     */
    public function all(): array
    {
        $data = $this->getInputData();
        return $data ?? [];
    }

    /**
     * Determine if the $keys is exist in parameters.
     *
     * @param array $keys
     * @return array []array [found, not-found]
     */
    public function hasInput(array $keys): array
    {
        $data = $this->getInputData();
        $found = [];

        foreach ($keys as $key) {
            if (Arr::has($data, $key)) {
                $found[] = $key;
            }
        }

        return [
            $found,
            array_diff($keys, $found),
        ];
    }

    /**
     * Determine if the $keys is exist in parameters.
     *
     * @param array|string $keys
     * @return bool
     */
    public function has(array|string $keys): bool
    {
        return Arr::has($this->getInputData(), $keys);
    }

    /**
     * Retrieve the data from request headers.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|string|null
     */
    public function header(string $key, mixed $default = null): mixed
    {
        if (!$this->hasHeader($key)) {
            return $default;
        }
        return $this->getHeaderLine($key);
    }

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path(): string
    {
        $pattern = trim($this->getPathInfo(), '/');

        return $pattern === '' ? '/' : $pattern;
    }

    /**
     * Returns the path being requested relative to the executed script.
     * The path info always starts with a /.
     * Suppose this request is instantiated from /mysite on localhost:
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'.
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo(): string
    {
        if ($this->pathInfo === null) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo ?? '';
    }

    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param mixed ...$patterns
     * @return bool
     */
    public function is(...$patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $this->decodedPath())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current decoded path info for the request.
     */
    public function decodedPath(): string
    {
        return rawurldecode($this->path());
    }

    /**
     * Returns the requested URI (path and query string).
     *
     * @return string The raw URI (i.e. not URI decoded)
     */
    public function getRequestUri(): string
    {
        if ($this->requestUri === null) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * Get the URL (no query string) for the request.
     */
    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
    }

    /**
     * Get the full URL for the request.
     */
    public function fullUrl(): string
    {
        $query = $this->getQueryString();

        return $this->url() . ($query ? '?' . $query : '');
    }

    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return string A normalized query string for the Request
     */
    public function getQueryString(): string
    {
        return $this->normalizeQueryString($this->getServerParams()['query_string'] ?? '');
    }

    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     * @return string A normalized query string for the Request
     */
    public function normalizeQueryString(string $qs): string
    {
        if (empty($qs)) {
            return '';
        }

        parse_str($qs, $arr);
        ksort($arr);

        return http_build_query($arr, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return Session
     */
    public function session(): Session
    {
        return app('session');
    }

    /**
     * Retrieve a cookie from the request.
     * @param string $key
     * @param null|mixed $default
     * @return array|mixed
     */
    public function cookie(string $key, $default = null): mixed
    {
        return data_get($this->getCookieParams(), $key, $default);
    }

    /**
     * Determine if a cookie is set on the request.
     * @param string $key
     * @return bool
     */
    public function hasCookie(string $key): bool
    {
        return !is_null($this->cookie($key));
    }

    /**
     * Retrieve a server variable from the request.
     *
     * @param string $key
     * @param null|mixed $default
     * @return null|array|string
     */
    public function server(string $key, $default = null): array|string|null
    {
        return data_get($this->getServerParams(), $key, $default);
    }

    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    /**
     * Retrieve a file from the request.
     *
     * @param string $key
     * @param mixed|null $default
     * @return UploadedFile|UploadedFile[]|null
     */
    public function file(string $key, mixed $default = null): UploadedFile|array|null
    {
        return Arr::get($this->getUploadedFiles(), $key, $default);
    }

    /**
     * @param string|null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function route(?string $key = null, $default = null): mixed
    {
        $routes = $this->getRequest()->getSwooleRequest()->routes ?? [];
        return $key ? ($routes[$key] ?? $default) : $routes;
    }

    /**
     * Determine if the uploaded data contains a file.
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        if ($file = $this->file($key)) {
            return $this->isValidFile($file);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $version
     * @return Request
     */
    public function withProtocolVersion(string $version): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    public function headers(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return string[]
     */
    public function getHeader(string $name): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return Request
     */
    public function withHeader(string $name, $value): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return Request
     */
    public function withAddedHeader(string $name, $value): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return Request
     */
    public function withoutHeader(string $name): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param StreamInterface $body
     * @return Request
     */
    public function withBody(StreamInterface $body): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $requestTarget
     * @return Request
     */
    public function withRequestTarget(mixed $requestTarget): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->getMethod();
    }

    /**
     * @param string $method
     * @return Request
     */
    public function withMethod(string $method): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param UriInterface $uri
     * @param false $preserveHost
     * @return Request
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string
     */
    public function getClientIp(): string
    {
        return $this->getClientIps()[0];
    }

    /**
     * @return string[]
     */
    public function getClientIps(): array
    {
        if (!empty($ips = $this->getHeader('x-forwarded-for'))) {
            return $ips;
        }
        if (!empty($ips = $this->getHeader('x-real-ip'))) {
            return $ips;
        }
        if (!empty($ips = $this->server('remote_addr'))) {
            return (array)$ips;
        }
        return ['127.0.0.1'];
    }

    /**
     * @return string
     */
    public function ip(): string
    {
        return $this->getClientIp();
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        $https = $this->getServerParams()['https'] ?? '';
        return !empty($https) && 'off' !== strtolower($https);
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $cookies
     * @return Request
     */
    public function withCookieParams(array $cookies): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $query
     * @return Request
     */
    public function withQueryParams(array $query): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param array $uploadedFiles
     * @return Request
     */
    public function withUploadedFiles(array $uploadedFiles): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return mixed
     */
    public function getParsedBody(): mixed
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param mixed $data
     * @return Request
     */
    public function withParsedBody($data): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Request
     */
    public function withAttribute(string $name, mixed $value): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $name
     * @return Request
     */
    public function withoutAttribute(string $name): Request
    {
        return $this->call(__FUNCTION__, func_get_args());
    }

    /**
     * Check that the given file is a valid SplFileInfo instance.
     * @param mixed $file
     * @return bool
     */
    protected function isValidFile(mixed $file): bool
    {
        return $file instanceof SplFileInfo && $file->getPath() !== '';
    }

    /**
     * Prepares the path info.
     */
    protected function preparePathInfo(): string
    {
        if (($requestUri = $this->getRequestUri()) === null) {
            return '/';
        }

        // Remove the query string from REQUEST_URI
        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        if ($requestUri !== '' && $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        return (string)$requestUri;
    }

    /*
     * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
     *
     * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     *
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     */
    protected function prepareRequestUri()
    {
        $requestUri = '';

        $serverParams = $this->getServerParams();
        if (isset($serverParams['request_uri'])) {
            $requestUri = $serverParams['request_uri'];

            if ($requestUri !== '' && $requestUri[0] === '/') {
                // To only use path and query remove the fragment.
                if (false !== $pos = strpos($requestUri, '#')) {
                    $requestUri = substr($requestUri, 0, $pos);
                }
            } else {
                // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path,
                // only use URL path.
                $uriComponents = parse_url($requestUri);

                if (isset($uriComponents['path'])) {
                    $requestUri = $uriComponents['path'];
                }

                if (isset($uriComponents['query'])) {
                    $requestUri .= '?' . $uriComponents['query'];
                }
            }
        }

        // normalize the request URI to ease creating sub-requests from this request
        $serverParams['request_uri'] = $requestUri;

        return $requestUri;
    }

    protected function getInputData(): array
    {
        return $this->storeParsedData(function () {
            $request = $this->getRequest();
            if (is_array($request->getParsedBody())) {
                $data = $request->getParsedBody();
            } else {
                $data = [];
            }

            return array_merge($data, $request->getQueryParams());
        });
    }

    protected function storeParsedData(callable $callback)
    {
        if (!Context::has($this->contextkeys['parsedData'])) {
            return Context::set($this->contextkeys['parsedData'], call($callback));
        }
        return Context::get($this->contextkeys['parsedData']);
    }

    protected function storeRequestProperty(string $key, $value): self
    {
        Context::set(__CLASS__ . '.properties.' . $key, value($value));
        return $this;
    }

    protected function getRequestProperty(string $key)
    {
        return Context::get(__CLASS__ . '.properties.' . $key);
    }

    protected function call($name, $arguments)
    {
        $request = $this->getRequest();
        if (!method_exists($request, $name)) {
            throw new RuntimeException('Method [' . $name . '] not exist.');
        }
        return $request->{$name}(...$arguments);
    }

    public function __call($name, $arguments)
    {
        $request = $this->getRequest();
        if (!method_exists($request, $name)) {
            throw new RuntimeException('Method [' . $name . '] not exist.');
        }
        return $request->{$name}(...$arguments);
    }

    protected function getRequest()
    {
        return Context::get(RequestInterface::class);
    }
}
