<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Contracts\ServiceProviderInterface;
use Swoole\Server;

class TranslateServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server, ?int $workerId): void
    {
        $app = app();
        $app->alias(Translate::class, 'translate');
        $app->singleton(Translate::class, Translate::class);
        $app->make('translate')->initialize();
    }
}