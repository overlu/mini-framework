<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class FilesystemServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the service provider.
     *
     * @param Server|null $server
     * @param int|null $workerId
     * @return void
     * @throws BindingResolutionException
     */
    public function register(?Server $server = null, ?int $workerId = null): void
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
        app()->singleton('files', function () {
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

//        app()->singleton('filesystem.disk', function () {
//            return app('filesystem')->disk($this->getDefaultDriver());
//        });
//
//        app()->singleton('filesystem.cloud', function () {
//            return app('filesystem')->disk($this->getCloudDriver());
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
        app()->singleton('filesystem', function ($app) {
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

    /**
     * @inheritDoc
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        // TODO: Implement boot() method.
    }
}
