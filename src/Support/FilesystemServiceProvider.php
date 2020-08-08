<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class FilesystemServiceProvider implements ServiceProviderInterface
{

    /**
     * @inheritDoc
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $app = app();
        $app->alias(Filesystem::class, 'files');
        $app->bind(Filesystem::class, Filesystem::class);
    }

    /**
     * @inheritDoc
     */
    public function boot(?Server $server, ?int $workerId): void
    {
        // TODO: Implement boot() method.
    }
}