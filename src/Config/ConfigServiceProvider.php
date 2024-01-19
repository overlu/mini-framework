<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Config;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;

class ConfigServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Mini\Contracts\Config::class, function () {
            return new Factory();
        });
        $this->app->alias(\Mini\Contracts\Config::class, 'config');
    }

    public function boot(): void
    {
    }

}