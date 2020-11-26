<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Support\Command;
use Throwable;

class Listener
{
    private static $instance;

    private static $config;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$config = config('listeners', []);
        }
        return self::$instance;
    }

    /**
     * @param $event
     * @param mixed ...$args
     * @throws Throwable
     */
    public function listen($event, ...$args): void
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
            Command::error($throwable);
        }
    }

    public function on(\Swoole\Server $server, $event)
    {
        try {
            if (isset(self::$config['server'][$event])) {
                $server->on($event, self::$config['server'][$event]);
            }
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
    }
}
