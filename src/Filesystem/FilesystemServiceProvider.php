<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->registerNativeFilesystem();

        $this->registerFlysystem();
    }

    /**
     * Register the native filesystem implementation.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerNativeFilesystem(): void
    {
        $this->app->singleton('files', function () {
            return new Filesystem;
        });
    }

    /**
     * Register the driver based filesystem.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerFlysystem(): void
    {
        $this->registerManager();

//        $this->app->singleton('filesystem.disk', function ($app) {
//            return $app['filesystem']->disk($this->getDefaultDriver());
//        });
//
//        $this->app->singleton('filesystem.cloud', function ($app) {
//            return $app['filesystem']->disk($this->getCloudDriver());
//        });
    }

    /**
     * Register the filesystem manager.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerManager(): void
    {
        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });
    }

    /**
     * Get the default file driver.
     *
     * @return string
     */
    protected function getDefaultDriver(): string
    {
        return config('filesystems.default');
    }

    /**
     * Get the default cloud based file driver.
     *
     * @return string
     */
    protected function getCloudDriver(): string
    {
        return config('filesystems.cloud');
    }

    public function boot(): void
    {
        //
    }
}
