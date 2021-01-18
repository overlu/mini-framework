<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session\Middleware;

use Carbon\Carbon;
use Exception;
use Mini\Config;
use Mini\Contracts\HttpMessage\SessionInterface;
use Mini\Contracts\MiddlewareInterface;
use Mini\Service\HttpMessage\Cookie\Cookie;
use Mini\Session\SessionManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $this->session = $this->sessionManager->getSession();
    }

    private function isSessionAvailable(): bool
    {
        return Config::getInstance()->has('session.handler');
    }

    /**
     * Store the current URL for the request if necessary.
     */
    private function storeCurrentUrl(): void
    {
        $request = request();
        if ($request->getMethod() === 'GET') {
            $this->session->setPreviousUrl((string)$request->getUri());
        }
    }

    /**
     * Get the session lifetime in seconds.
     */
    private function getCookieExpirationDate(): int
    {
        if (config('session.options.expire_on_close')) {
            $expirationDate = 0;
        } else {
            $expireSeconds = config('session.options.cookie_lifetime', 5 * 60 * 60);
            $expirationDate = Carbon::now()->addSeconds($expireSeconds)->getTimestamp();
        }
        return $expirationDate;
    }

    /**
     * @throws Exception
     */
    private function addCookieToResponse()
    {
        $uri = request()->getUri();

        return response()->withCookie(new Cookie(
            $this->session->getName(),
            $this->session->getId(),
            $this->getCookieExpirationDate(),
            '/',
            config('session.options.domain', $uri->getHost()),
            strtolower($uri->getScheme()) === 'https', true
        ));
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function before()
    {
        if (!$this->isSessionAvailable()) {
            return;
        }
        $this->sessionManager->start();
    }

    /**
     * @param ResponseInterface $response
     * @return mixed|ResponseInterface
     * @throws Exception
     */
    public function after(ResponseInterface $response)
    {
        $this->storeCurrentUrl();
        $this->sessionManager->end();
        return $this->addCookieToResponse();
    }
}
