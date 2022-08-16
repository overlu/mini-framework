<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Exception;
use Mini\Contracts\HttpMessage\SessionInterface;
use Mini\Contracts\MiddlewareInterface;
use Mini\Service\HttpMessage\Cookie\Cookie;
use Psr\Http\Message\ResponseInterface;

class SessionMiddleware implements MiddlewareInterface
{
    /**
     * @var SessionManager
     */
    private SessionManager $sessionManager;

    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    public function __construct()
    {
        $this->sessionManager = app('session.manager');
    }

    private function isSessionAvailable(): bool
    {
        return app('config')->has('session.driver');
    }

    /**
     * Get the session lifetime in seconds.
     */
    private function getCookieExpirationDate(): int
    {
        if (config('session.options.expire_on_close')) {
            $expirationDate = 0;
        } else {
            $expirationDate = time() + (int)config('session.lifetime', 120) * 60;
        }
        return $expirationDate;
    }

    /**
     * @param string $method
     * @param string $className
     * @return void
     * @throws Exception
     */
    public function before(string $method, string $className)
    {
        if (!$this->isSessionAvailable()) {
            return;
        }
        $this->sessionManager->start();
    }

    /**
     * @param ResponseInterface $response
     * @param string $className
     * @return ResponseInterface
     */
    public function after(ResponseInterface $response, string $className): ResponseInterface
    {
        if (!$this->isSessionAvailable()) {
            return $response;
        }
        $session = $this->sessionManager->getSession();
        if (!$session) {
            return $response;
        }
        $request = request();
        if ($request->getMethod() === 'GET') {
            $session->setPreviousUrl((string)$request->getUri());
        }
        $this->sessionManager->end();
        $uri = $request->getUri();
        $domain = config('session.domain', $uri->getHost());
        $resp = $response->withCookie(new Cookie(
            $session->getName(),
            $session->getId(),
            $this->getCookieExpirationDate(),
            config('session.path', '/'),
            $domain ?: $uri->getHost(),
            strtolower($uri->getScheme()) === 'https', true,
            config('session.http_only', true),
        ));
        $session->reset();
        return $resp;
    }
}
