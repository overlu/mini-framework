<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Watch;

use Mini\Support\Command;
use Swoole\Timer;

class Runner
{
    public static int $scan_interval = 1000;

    public static function start(): void
    {
        $serve = new WatchServer();
        Command::infoWithTime( '👀 watching start...');
        $serve->state();
        Timer::tick(static::$scan_interval, [$serve, 'watch']);
    }
}