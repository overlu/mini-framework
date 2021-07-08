<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

/**
 * Class ConsoleServiceProvider
 * @package Mini\Console
 */
class ConsoleServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        app()->singleton('console', function () {
            return new Console();
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
