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
use Throwable;

class LoggingServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        try {
            $config = config('logging');
            @Seaslog::setBasePath($config['default_base_path']);
            @Seaslog::setLogger($config['default_logger']);
        } catch (Throwable $throwable) {
            app('exception')->logError($throwable);
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