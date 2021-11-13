<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;

class TranslateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    /**
     * @throws BindingResolutionException|\ReflectionException
     */
    public function boot(): void
    {
        $this->app->alias(Translate::class, 'translate');
        $this->app->singleton(Translate::class, Translate::class);
    }
}