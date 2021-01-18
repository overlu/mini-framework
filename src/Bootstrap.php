<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Contracts\ServiceProviderInterface;
use RuntimeException;
use Swoole\Server;

/**
 * Class Bootstrap
 * @package Mini
 */
class Bootstrap
{
    use Singleton;

    /**
     * @var ServiceProviderInterface[]
     */
    private array $serviceProviders;


    private array $middlewares;

    private function __construct()
    {
        $this->serviceProviders = config('app.providers', []);
        $this->middlewares = config('app.middlewares', []);
    }

    /**
     * add serviceProvider
     * @param string $serviceProvider
     */
    public function addServiceProvider(string $serviceProvider): void
    {
        if (!new $serviceProvider instanceof ServiceProviderInterface) {
            throw new RuntimeException($serviceProvider . ' should instanceof ' . ServiceProviderInterface::class);
        }
        $this->serviceProviders[] = $serviceProvider;
    }

    /**
     * remove serviceProvider
     * @param string $serviceProvider
     */
    public function removeServiceProvider(string $serviceProvider): void
    {
        if (!new $serviceProvider instanceof ServiceProviderInterface) {
            throw new RuntimeException($serviceProvider . ' should instanceof ' . ServiceProviderInterface::class);
        }
        if (isset($this->serviceProviders[$serviceProvider])) {
            unset($this->serviceProviders[$serviceProvider]);
        }
    }

    /**
     * @return ServiceProviderInterface[]
     */
    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * register serviceProviders
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function registerServiceProviders(?Server $server, ?int $workerId): void
    {
        foreach ($this->serviceProviders as &$service) {
            $service = is_object($service) ? $service : new $service;
            if ($service instanceof ServiceProviderInterface) {
                $service->register($server, $workerId);
            }
        }
    }

    /**
     * boot serviceProviders
     * @param Server|null $server
     * @param int|null $workerId
     */
    public function bootServiceProviders(?Server $server, ?int $workerId): void
    {
        foreach ($this->serviceProviders as $service) {
            if ($service instanceof ServiceProviderInterface) {
                $service->boot($server, $workerId);
            }
        }
    }

    // TODO
}