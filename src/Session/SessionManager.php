<?php

declare(strict_types=1);
/**
 * This file is part of Mini.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Mini\Session;

use Exception;
use Mini\Context;
use Mini\Contract\ConfigInterface;
use Mini\Contracts\HttpMessage\SessionInterface;
use Mini\Support\Str;
use Psr\Container\ContainerInterface;
use SessionHandlerInterface;

class SessionManager
{
    /**
     * Get session name
     * @return string
     */
    public function getSessionName(): string
    {
        return config('session.options.session_name', 'MINI_SESSION_ID');
    }

    /**
     * @return void
     * @throws Exception
     */
    public function start(): void
    {
        $sessionId = $this->parseSessionId();
        $session = new Session($this->getSessionName(), $this->buildSessionHandler());
        $session->setId($sessionId);
        if (!$session->start()) {
            throw new \RuntimeException('Start session failed.');
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

    protected function buildSessionHandler(): SessionHandlerInterface
    {
        $handler = config('session.handler');
        if (!$handler) {
            throw new \InvalidArgumentException('Invalid handler of session');
        }
        return ;
    }
}
