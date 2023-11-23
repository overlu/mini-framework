<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\AbstractServiceProvider;
use ReflectionException;

class MiniDBServiceProvider extends AbstractServiceProvider
{
    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton('db.mini.pool', function () {
            return new Pool();
        });
        $this->app->singleton('db.mini', function () {
            return new DB();
        });
    }

    /**
     */
    public function boot(): void
    {
    }
}
