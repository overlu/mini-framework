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
    /**
     * @throws BindingResolutionException|\ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton('config', function () {
            return new Factory();
        });
    }

    public function boot(): void
    {
    }

}