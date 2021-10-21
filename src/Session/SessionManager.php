<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Exception;
use Mini\Context;
use Mini\Contract\ConfigInterface;
use Mini\Contracts\HttpMessage\SessionInterface;
use Mini\Support\Str;
use Psr\Container\ContainerInterface;
use RuntimeException;

class SessionManager
{
    /**
     * @return void
     * @throws Exception
     */
    public function start(): void
    {
        $sessionId = $this->parseSessionId();
        /**
         * @var $session Session
         */
        $session = app('session');
        $session->setId($sessionId);
        if (!$session->start()) {
            throw new RuntimeException('Start session failed.');
        }
        $this->setSession($session);
    }

    public function end(): void
    {
        $this->getSession()->save();
    }

    public function getSession(): SessionInterface
    {
        return Context::get(SessionInterface::class);
    }

    public function setSession(SessionInterface $session): void
    {
        Context::set(SessionInterface::class, $session);
    }

    /**
     * @return string|null
     * @throws Exception
     */
    protected function parseSessionId(): ?string
    {
        $sessionId = request()->cookie($this->getSessionName(), Str::random(40));
        return (string)$sessionId;
    }

    /**
     * Get session name
     * @return string
     */
    protected function getSessionName(): string
    {
        return config('session.options.session_name', 'MINI_SESSION_ID');
    }

}
