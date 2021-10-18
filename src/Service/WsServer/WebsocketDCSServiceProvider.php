<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Service\HttpServer\RouteService;
use Mini\Support\ServiceProvider;
use Swoole\Server;

class WebsocketDCSServiceProvider extends ServiceProvider
{
    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server = null, ?int $workerId = null): void
    {
        RouteService::registerWsRoute(['/{authcode:[0-9a-zA-Z]{40}}/{host}', DCS::class]);
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     * @throws BindingResolutionException
     */
    public function boot(?Server $server = null, ?int $workerId = null): void
    {
        Client::register();
        $this->app->singleton('dcs', function () {
            return Client::getInstance();
        });
    }
}