<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use Mini\Contracts\ServiceProviderInterface;
use \Seaslog;
use Swoole\Server;

class LoggingServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $config = config('logging');
        Seaslog::setBasePath($config['default_basepath']);
        Seaslog::setLogger($config['default_logger']);
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server, ?int $workerId): void
    {
    }
}