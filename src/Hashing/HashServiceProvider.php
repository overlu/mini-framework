<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Hashing;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\Hasher;
use Mini\Service\AbstractServiceProvider;
use ReflectionException;

class HashServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     * @throws BindingResolutionException|ReflectionException
     */
    public function register(): void
    {
        $this->app->singleton(Hasher::class, function () {
            return new HashManager($this->app);
        });
        $this->app->alias(Hasher::class, 'hash');
        $this->app->singleton('hash.driver', function () {
            return $this->app['hash']->driver();
        });
    }

    public function boot(): void
    {
        //
    }
}
