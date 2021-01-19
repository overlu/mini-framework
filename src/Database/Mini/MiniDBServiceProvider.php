<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class MiniDBServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        $config = config('database.connections', []);
        if (!empty($config)) {
            Pool::getInstance($config);
        }
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
    }
}
