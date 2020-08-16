<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql;

use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class OrmServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $config = config('database', []);
        if (!empty($config)) {
            new DatabaseBoot($config);
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
