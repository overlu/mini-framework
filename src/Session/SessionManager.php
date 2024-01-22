<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Exception;
use Mini\Context;
use Mini\Contracts\Session;
use Mini\Support\Str;
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
        if ($session = $this->getSession()) {
            $session->save();
        }
    }

    public function getSession(): ?Session
    {
        return Context::get(Session::class);
    }

    public function setSession(Session $session): void
    {
        Context::set(Session::class, $session);
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
