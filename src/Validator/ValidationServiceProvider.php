<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class ValidationServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function boot(?Server $server, ?int $workerId): void
    {
        $app = app();
        $app->alias(Factory::class, 'validator');
        $app->singleton(Factory::class, Factory::class);
    }
}