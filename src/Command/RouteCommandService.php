<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Command;

use Mini\Support\Command;

class RouteCommandService extends BaseCommandService
{
    public static string $command = 'route:clear';

    public static string $description = 'clear route config cache.';

    public function run()
    {
        @unlink(BASE_PATH . '/storage/app/route.cache');
        Command::info('route cache cleared.');
    }
}