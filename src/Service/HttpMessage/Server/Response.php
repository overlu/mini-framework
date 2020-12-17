<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpMessage\Server;

use Mini\Contracts\Support\Sendable;
use Mini\Service\HttpMessage\Cookie\Cookie;
use Mini\Contracts\HttpMessage\FileInterface;
use Mini\Service\HttpMessage\Stream\SwooleStream;

class Response extends \Mini\Service\HttpMessage\Base\Response implements Sendable
{
    /**
     * @var null|\Swoole\Http\Response
     */
    protected \Swoole\Http\Response $swooleResponse;

    /**
     * @var array
     */
    protected array $cookies = [];

    public function __construct(\Swoole\Http\Response $response = null)
    {
        $this->swooleResponse = $response;
    }

    /**
     * Handle response and send.
     * @param bool $withContent
     * @return mixed|void
     */
    public function send(bool $withContent = true)
    {
        if (!$this->getSwooleResponse()) {
            return;
        }

        $this->buildSwooleResponse($this->swooleResponse, $this);
        $content = $this->getBody();
        if ($content instanceof FileInterface) {
            return $this->swooleResponse->sendfile($content->getFilename());
        }
        if ($withContent && $content = $content->getContents()) {
            $this->swooleResponse->write($content);
        }
        $this->swooleResponse->end();
    }

    /**
     * Returns an instance with body content.
     * @param string $content
     * @return Response
     */
    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->stream = new SwooleStream($content);
        return $clone;
    }

    /**
     * Return an instance with specified cookies.
     * @param Cookie $cookie
     * @return Response
     */
    public function withCookie(Cookie $cookie): self
    {
        $clone = clone $this;
        $clone->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
        return $clone;
    }

    /**
     * Return all cookies.
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getSwooleResponse(): \Swoole\Http\Response
    {
        return $this->swooleResponse;
    }

    public function setSwooleResponse(\Swoole\Http\Response $swooleResponse): self
    {
        $this->swooleResponse = $swooleResponse;
        return $this;
    }

    /**
     * Keep this method at public level,
     * allows the proxy class to override this method,
     * or override the method that used this method.
     * @param \Swoole\Http\Response $swooleResponse
     * @param Response $response
     */
    public function buildSwooleResponse(\Swoole\Http\Response $swooleResponse, Response $response): void
    {
        /*
         * Headers
         */
        foreach ($response->getHeaders() as $key => $value) {
            $swooleResponse->header($key, implode(';', $value));
        }

        /*
         * Cookies
         */
        foreach ((array)$this->cookies as $domain => $paths) {
            foreach ($paths ?? [] as $path => $item) {
                foreach ($item ?? [] as $name => $cookie) {
                    if ($cookie instanceof Cookie) {
                        $value = $cookie->isRaw() ? $cookie->getValue() : rawurlencode($cookie->getValue());
                        $swooleResponse->rawcookie($cookie->getName(), $value, $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly(), (string)$cookie->getSameSite());
                    }
                }
            }
        }

        /*
         * Status code
         */
        $swooleResponse->status($response->getStatusCode());
    }
}
