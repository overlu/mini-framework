<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mini;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;
use ReflectionException;

class MiniDBServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function register(): void
    {
        $config = config('database.connections', []);
        if (!empty($config)) {
            $this->app->singleton('db.mini', function () use ($config) {
                return new Pool($config);
            });
        }
    }

    /**
     */
    public function boot(): void
    {
    }
}
