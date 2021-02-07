<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Context;

/**
 * Class Reponse
 * @method static \Psr\Http\Message\ResponseInterface json(array|\Mini\Contracts\Support\Arrayable|\Mini\Contracts\Support\Jsonable $data)
 * @method static \Psr\Http\Message\ResponseInterface xml(array|\Mini\Contracts\Support\Arrayable|\Mini\Contracts\Support\Xmlable $data, string $root = 'root')
 * @method static \Psr\Http\Message\ResponseInterface raw($data)
 * @method static \Psr\Http\Message\ResponseInterface redirect(string $toUrl, int $status = 302, string $schema = 'http')
 * @method static \Psr\Http\Message\ResponseInterface download(string $file, string $name = '')
 * @method static \Psr\Http\Message\ResponseInterface withCookie(\Mini\Service\HttpMessage\Cookie\Cookie $cookie)
 * @method static \Psr\Http\Message\ResponseInterface withProtocolVersion($version)
 * @method static \Psr\Http\Message\ResponseInterface withHeader(string $name, string|string[] $value)
 * @method static \Psr\Http\Message\ResponseInterface withHeaders(array $headers)
 * @method static \Psr\Http\Message\ResponseInterface withAddedHeader(string $name, string|string[] $value)
 * @method static \Psr\Http\Message\ResponseInterface withoutHeader($name)
 * @method static \Psr\Http\Message\ResponseInterface withBody(\Psr\Http\Message\StreamInterface $body)
 * @method static \Psr\Http\Message\ResponseInterface withStatus(int $code, string $reasonPhrase = '')
 * @method static \Psr\Http\Message\ResponseInterface withContent(string $content)
 * @method static \Psr\Http\Message\ResponseInterface withAttribute(string $name, $value)
 * @method static \Psr\Http\Message\ResponseInterface withCharset(string $charset)
 * @method static \Psr\Http\Message\ResponseInterface setCharset(string $charset)
 * @method static mixed view(string|array $view, array $data = [], int $status = 200, array $headers = [])
 * @method static string getProtocolVersion()
 * @method static array getHeaders()
 * @method static bool hasHeader(string $name)
 * @method static bool isInvalid()
 * @method static bool isInformational()
 * @method static bool isSuccessful()
 * @method static bool isRedirection()
 * @method static bool isClientError()
 * @method static bool isServerError()
 * @method static bool isOk()
 * @method static bool isForbidden()
 * @method static bool isNotFound()
 * @method static bool isRedirect(string|null $location = null)
 * @method static bool isEmpty()
 * @method static bool isMultipart()
 * @method static string[] getHeader($name)
 * @method static string getHeaderLine($name)
 * @method static \Psr\Http\Message\StreamInterface getBody()
 * @method static int getStatusCode()
 * @method static string getReasonPhraseByCode(int $code)
 * @method static string getReasonPhrase()
 * @method static string getCharset()
 * @method static mixed send(bool $withContent = true)
 * @method static array getCookies()
 * @method static string getContentType()
 * @method static array getAttributes()
 * @method static mixed getAttribute(string $name, $default = null)
 * @method static array|string getHeaderField(string $name, string|null $wantedPart = '0', string $firstName = '0')
 * @method static \Swoole\Http\Response getSwooleResponse()
 * @method static \Psr\Http\Message\ResponseInterface setSwooleResponse(\Swoole\Http\Response $swooleResponse)
 *
 * @package Mini\Facades
 */
class Response extends Facade
{
    protected static function getFacadeAccessor()
    {
        if (!Context::has('IsInRequestEvent')) {
            throw new \RuntimeException("Not In Request Environment.");
        }
        return \Mini\Contracts\HttpMessage\ResponseInterface::class;
    }
}