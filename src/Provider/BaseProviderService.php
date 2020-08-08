<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Provider;

use Mini\Contracts\ServiceProviderInterface;
use Mini\Singleton;
use Swoole\Server;

class BaseProviderService
{
    use Singleton;

    private array $services;

    private function __construct()
    {
        $this->services = config('app.providers', []);
    }

    public function register(?Server $server, ?int $workerId): void
    {
        foreach ($this->services as $service) {
            $service = new $service;
            if ($service instanceof ServiceProviderInterface) {
                $service->register($server, $workerId);
            }
        }
    }

    public function boot(?Server $server, ?int $workerId): void
    {
        foreach ($this->services as $service) {
            $service = new $service;
            if ($service instanceof ServiceProviderInterface) {
                $service->boot($server, $workerId);
            }
        }
    }
}