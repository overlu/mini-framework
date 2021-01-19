<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Throwable;

/**
 * Class Listener
 * @package Mini
 */
class Listener
{
    private static Listener $instance;

    private static array $config = [];

    private function __construct()
    {
    }

    public static function getInstance(): Listener
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$config = config('listeners', []);
        }
        return self::$instance;
    }

    /**
     * @param string $event
     * @param mixed ...$args
     * @throws Throwable
     */
    public function listen(string $event, ...$args): void
    {
        try {
            if (isset(self::$config['server'][$event]) && $listener = self::$config['server'][$event]) {
                if (is_array($listener) && method_exists($listener[0], 'getInstance')) {
                    $listener[0]::getInstance()->{$listener[1]}(...$args);
                } else {
                    call($listener, $args);
                }
            }
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }

    /**
     * @param \Swoole\Server $server
     * @param string $event
     */
    public function on(\Swoole\Server $server, string $event): void
    {
        try {
            if (isset(self::$config['server'][$event])) {
                $server->on($event, self::$config['server'][$event]);
            }
        } catch (Throwable $throwable) {
            app('exception')->throw($throwable);
        }
    }
}
