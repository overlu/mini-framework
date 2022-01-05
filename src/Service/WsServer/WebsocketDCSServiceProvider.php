<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\ServiceProvider;

class WebsocketDCSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * @throws BindingResolutionException|\ReflectionException
     */
    public function boot(): void
    {
        $this->app['route']->registerWsRoute(['/{authcode:[0-9a-zA-Z]{40}}/{host}', DCS::class]);
        if ($this->worker_id === 1) {
            Client::register();
        }
        $this->app->singleton('dcs', function () {
            return new Client();
        });
    }
}