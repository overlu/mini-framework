<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Redis;

use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class RedisServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $config = config('redis', []);
        if (!empty($config)) {
            Pool::getInstance($config);
        }
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server, ?int $workerId): void
    {
    }

}
