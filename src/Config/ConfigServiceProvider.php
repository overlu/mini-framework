<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Config;

use Mini\Config;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;
use Swoole\Server;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->app->singleton('config', function () {
            return Config::getInstance();
        });
    }

    public function boot(): void
    {
    }

}