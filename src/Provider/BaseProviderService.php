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

    /**
     * @var ServiceProviderInterface[]
     */
    private array $serviceProviders;

    private function __construct()
    {
        $this->serviceProviders = config('app.providers', []);
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function register(?Server $server, ?int $workerId): void
    {
        foreach ($this->serviceProviders as &$service) {
            $service = is_object($service) ? $service : new $service;
            if ($service instanceof ServiceProviderInterface) {
                $service->register($server, $workerId);
            }
        }
    }

    /**
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function boot(?Server $server, ?int $workerId): void
    {
        foreach ($this->serviceProviders as $service) {
            if ($service instanceof ServiceProviderInterface) {
                $service->boot($server, $workerId);
            }
        }
    }
}