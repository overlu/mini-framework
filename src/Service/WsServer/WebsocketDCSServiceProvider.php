<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Contracts\ServiceProviderInterface;
use Mini\Service\HttpServer\RouteService;
use Swoole\Server;

class WebsocketDCSServiceProvider implements ServiceProviderInterface
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
        app()->singleton('dcs', function () {
            return Client::getInstance();
        });
    }
}