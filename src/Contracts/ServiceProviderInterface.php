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
     */
    public function register(): void;

    /**
     * Bootstrap any application services.
     */
    public function boot(): void;
}