<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;
use ReflectionException;

class ValidationServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
    }

    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function boot(): void
    {
        $this->app->alias(Factory::class, 'validator');
        $this->app->singleton(Factory::class, Factory::class);
    }
}