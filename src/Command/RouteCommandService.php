<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;
use Swoole\Process;

class RouteCommandService extends AbstractCommandService
{
    public function handle(Process $process): void
    {
        @unlink(BASE_PATH . '/storage/app/route.cache');
        Command::info('route cache cleared.');
    }

    public function getCommand(): string
    {
        return 'route:clear';
    }

    public function getCommandDescription(): string
    {
        return 'clear route config cache.';
    }
}