<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpMessage\Server;

use Mini\Context;
use Mini\Contracts\Support\Sendable;
use Mini\Service\HttpMessage\Cookie\Cookie;
use Mini\Contracts\HttpMessage\FileInterface;
use Mini\Service\HttpMessage\Stream\SwooleStream;

class Response extends \Mini\Service\HttpMessage\Base\Response implements Sendable
{
    /**
     * @var null|\Swoole\Http\Response
     */
    protected ?\Swoole\Http\Response $swooleResponse = null;

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
     * @return void
     */
    public function send(bool $withContent = true):void
    {
        if (!$this->getSwooleResponse()) {
            return;
        }

        $this->buildSwooleResponse();
        $content = $this->getBody();
        if ($content instanceof FileInterface) {
            $this->swooleResponse->sendfile($content->getFilename());
            $this->swooleResponse->end();
            return;
        }
        if ($withContent && $content = $content->getContents()) {
            if (Context::has('hasWriteContent')) {
                $this->swooleResponse->write($content);
                $this->swooleResponse->end();
                return;
            }
            $this->swooleResponse->end($content);
            return;
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
     */
    public function buildSwooleResponse(): void
    {
        /*
         * Headers
         */
        foreach ($this->getHeaders() as $key => $value) {
            $this->swooleResponse->header($key, implode(';', $value));
        }

        /*
         * Cookies
         */
        foreach ((array)$this->cookies as $domain => $paths) {
            foreach ($paths ?? [] as $path => $item) {
                foreach ($item ?? [] as $name => $cookie) {
                    if ($cookie instanceof Cookie) {
                        $value = $cookie->isRaw() ? $cookie->getValue() : rawurlencode($cookie->getValue());
                        $this->swooleResponse->rawcookie($cookie->getName(), $value, $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly(), (string)$cookie->getSameSite());
                    }
                }
            }
        }

        /*
         * Status code
         */
        $this->swooleResponse->status($this->getStatusCode());
    }
}
