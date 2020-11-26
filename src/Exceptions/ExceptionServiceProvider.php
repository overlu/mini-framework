<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use App\Exceptions\Handler;
use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class ExceptionServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    public function register(?Server $server, ?int $workerId): void
    {
        $app = app();
        $handler = class_exists(Handler::class) ? Handler::class : \Mini\Exceptions\Handler::class;
        $app->alias($handler, 'exception');
        $app->singleton($handler, $handler);
    }

    public function boot(?Server $server, ?int $workerId): void
    {
        // TODO: Implement boot() method.
    }
}
