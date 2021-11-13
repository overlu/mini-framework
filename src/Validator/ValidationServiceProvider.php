<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    /**
     * @throws BindingResolutionException|\ReflectionException
     */
    public function boot(): void
    {
        $this->app->alias(Factory::class, 'validator');
        $this->app->singleton(Factory::class, Factory::class);
    }
}