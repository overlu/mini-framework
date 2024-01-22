<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use Mini\Service\AbstractServiceProvider;

class FilesystemServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
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
     */
    protected function registerNativeFilesystem(): void
    {
        $this->app->singleton(\Mini\Contracts\File::class, function () {
            return new Filesystem;
        });
        $this->app->alias(\Mini\Contracts\File::class, 'files');
    }

    /**
     * Register the driver based filesystem.
     *
     * @return void
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
     */
    protected function registerManager(): void
    {
        $this->app->singleton(\Mini\Contracts\Storage::class, function ($app) {
            return new FilesystemManager($app);
        });
        $this->app->alias(\Mini\Contracts\Storage::class, 'filesystem');
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
    }
}
