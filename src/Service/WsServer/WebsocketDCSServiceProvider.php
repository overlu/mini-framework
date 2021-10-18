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

class WebsocketDCSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        RouteService::registerWsRoute(['/{authcode:[0-9a-zA-Z]{40}}/{host}', DCS::class]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        Client::register();
        $this->app->singleton('dcs', function () {
            return Client::getInstance();
        });
    }
}