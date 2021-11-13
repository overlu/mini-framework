<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Console;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;
use ReflectionException;

/**
 * Class ConsoleServiceProvider
 * @package Mini\Console
 */
class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton('console', function () {
            return new Console();
        });
    }

    public function boot(): void
    {
    }
}
