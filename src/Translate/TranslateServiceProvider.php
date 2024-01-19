<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Service\AbstractServiceProvider;

class TranslateServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->app->alias(Translate::class, 'translate');
        $this->app->singleton(Translate::class, Translate::class);
    }
}