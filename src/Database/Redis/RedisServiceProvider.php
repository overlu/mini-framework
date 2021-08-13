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
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        app()->singleton('redis', function () {
            return new Redis();
        });
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
    }
}
