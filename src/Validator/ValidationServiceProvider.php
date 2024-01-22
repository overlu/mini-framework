<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator;

use Mini\Service\AbstractServiceProvider;

class ValidationServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->app->singleton(\Mini\Contracts\Validator::class, Factory::class);
        $this->app->alias(\Mini\Contracts\Validator::class, 'validator');
    }
}