<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Mini\Service\AbstractServiceProvider;
use Mini\Session\Drivers\FileSessionDriver;
use Mini\Session\Drivers\NullSessionHandler;
use Mini\Session\Drivers\RedisSessionDriver;
use SessionHandlerInterface;

/**
 * Class SessionServiceProvider
 * @package Mini\Session
 */
class SessionServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array|string[]
     */
    protected array $drivers = [
        'file' => FileSessionDriver::class,
        'redis' => RedisSessionDriver::class,
        'null' => NullSessionHandler::class
    ];

    public function register(): void
    {
        //
    }

    protected function registerSession(): void
    {
        $this->app->singleton(\Mini\Contracts\Session::class, function () {
            return new Session($this->getSessionName(), $this->buildSessionHandler());
        });
        $this->app->alias(\Mini\Contracts\Session::class, 'session');
        $this->app->singleton('session.manager', function () {
            return new SessionManager();
        });
    }

    /**
     * @return SessionHandlerInterface
     */
    protected function buildSessionHandler(): SessionHandlerInterface
    {
        $handler = config('session.driver', 'null');
        $handler = is_null($handler) ? 'null' : $handler;
        if (!$handler || !isset($this->drivers[$handler])) {
            throw new \InvalidArgumentException('Invalid handler of session');
        }
        return new $this->drivers[$handler];
    }

    /**
     * Get session name
     * @return string
     */
    protected function getSessionName(): string
    {
        return config('session.session_name', 'MINI_SESSION_ID');
    }

    public function boot(): void
    {
        $this->registerSession();
    }
}