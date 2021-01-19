<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use Swoole\Server;

interface ServiceProviderInterface
{
    /**
     * Register any application services.
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void;

    /**
     * Bootstrap any application services.
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server = null, ?int $workerId = null): void;
}