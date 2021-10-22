<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Context;

/**
 * Class Request
 * @method static array all()
 * @method static mixed get(null|string $key = null, $default = null)
 * @method static array only(array $keys)
 * @method static mixed query(null|string $key = null, $default = null)
 * @method static mixed post(null|string $key = null, $default = null)
 * @method static mixed input(string $key = null, $default = null)
 * @method static mixed inputs(array $keys, array $default = [])
 * @method static array hasInput(array $keys)
 * @method static bool has(string|array $keys)
 * @method static mixed header(string $key, $default = null)
 * @method static string path()
 * @method static string getPathInfo()
 * @method static bool is(...$patterns)
 * @method static bool isAjax()
 * @method static bool isXmlHttpRequest()
 * @method static string decodedPath()
 * @method static string getRequestUri()
 * @method static string url()
 * @method static string fullUrl()
 * @method static string getQueryString()
 * @method static string normalizeQueryString(string $queryString)
 * @method static mixed cookie(string $key, $default = null)
 * @method static bool hasCookie(string $key)
 * @method static mixed server(string $key, $default = null)
 * @method static bool isMethod(string $method)
 * @method static mixed route(null|string $key = null, $default = null)
 * @method static \Mini\Service\HttpMessage\Upload\UploadedFile|\Mini\Service\HttpMessage\Upload\UploadedFile[] file(string $key, $default = null)
 * @method static bool hasFile(string $key)
 * @method static string getProtocolVersion()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withProtocolVersion(string $version)
 * @method static array getHeaders()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withHeaders(array $headers)
 * @method static bool hasHeader($name)
 * @method static array getHeader($name)
 * @method static string getHeaderLine($name)
 * @method static string|array getHeaderField($name, string|null $wantedPart = '0', $firstName = '0')
 * @method static string getContentType()
 * @method static bool isMultipart()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withHeader($name, $value)
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withAddedHeader($name, $value)
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withoutHeader($name)
 * @method static \Psr\Http\Message\StreamInterface getBody()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withBody(\Psr\Http\Message\StreamInterface $body)
 * @method static string getRequestTarget()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withRequestTarget($requestTarget)
 * @method static string getMethod()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withMethod($method)
 * @method static \Psr\Http\Message\UriInterface getUri()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false)
 * @method static array getServerParams()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withServerParams(array $serverParams)
 * @method static string getClientIp()
 * @method static string ip()
 * @method static string getScheme()
 * @method static bool isSecure()
 * @method static array getCookieParams()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withCookieParams(array $cookies)
 * @method static array getQueryParams()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface addQueryParam(string $name, $value)
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withQueryParams(array $query)
 * @method static \Psr\Http\Message\UploadedFileInterface[] getUploadedFiles()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withUploadedFiles(\Psr\Http\Message\UploadedFileInterface[] $uploadedFiles)
 * @method static array|object|null getParsedBody()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withBodyParams($data)
 * @method static mixed getBodyParams()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface addParserBody(string $name, $value)
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withParsedBody(array|object|null $data)
 * @method static array getAttributes()
 * @method static mixed getAttribute(string $name, $default = null)
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withAttribute(string $name, $value)
 * @method static \Mini\Contracts\HttpMessage\RequestInterface withoutAttribute($name)
 * @method static \Swoole\Http\Request getSwooleRequest()
 * @method static \Mini\Contracts\HttpMessage\RequestInterface setSwooleRequest(\Swoole\Http\Request $swooleRequest)
 * @package Mini\Facades
 * @see \Mini\Service\HttpServer\Request
 */
class Request extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        if (!Context::has('IsInRequestEvent')) {
            throw new \RuntimeException("Not In Request Environment.");
        }
        return \Mini\Contracts\HttpMessage\RequestInterface::class;
    }
}