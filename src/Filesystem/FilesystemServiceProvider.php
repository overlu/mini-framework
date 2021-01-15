<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem;

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
     */
    public function register(?Server $server, ?int $workerId): void
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
        app()->singleton('files', function () {
            return new Filesystem;
        });
    }

    /**
     * Register the driver based filesystem.
     *
     * @return void
     */
    protected function registerFlysystem(): void
    {
        $this->registerManager();

        /*app()->singleton('filesystem.disk', function () {
            return app('filesystem')->disk($this->getDefaultDriver());
        });

        app()->singleton('filesystem.cloud', function () {
            return app('filesystem')->disk($this->getCloudDriver());
        });*/
    }

    /**
     * Register the filesystem manager.
     *
     * @return void
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
    public function boot(?Server $server, ?int $workerId): void
    {
        // TODO: Implement boot() method.
    }
}
