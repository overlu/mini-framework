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
     * @param $listener
     * @param mixed ...$args
     * @throws Throwable
     */
    public function listen($listener, ...$args): void
    {
        try {
            $listeners = self::$config[$listener] ?? [];
            while ($listeners) {
                [$class, $func] = array_shift($listeners);
                $class::getInstance()->{$func}(...$args);
            }
        } catch (Throwable $throwable) {
            Command::error($throwable);
        }
    }
}
