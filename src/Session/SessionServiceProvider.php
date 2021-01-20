<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Session;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Mini\Session\Drivers\FileSessionDriver;
use Mini\Session\Drivers\NullSessionHandler;
use Mini\Session\Drivers\RedisSessionDriver;
use SessionHandlerInterface;
use Swoole\Server;

/**
 * Class SessionServiceProvider
 * @package Mini\Session
 */
class SessionServiceProvider implements ServiceProviderInterface
{
    /**
     * @var array|string[]
     */
    protected array $drivers = [
        'file' => FileSessionDriver::class,
        'redis' => RedisSessionDriver::class,
        'null' => NullSessionHandler::class
    ];

    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        $this->registerSession();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function registerSession(): void
    {
        app()->singleton('session', function () {
            return new Session($this->getSessionName(), $this->buildSessionHandler());
        });
        app()->singleton('session.manager', function () {
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

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        // TODO: Implement boot() method.
    }
}