<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Bootstrap;

use Mini\Support\ServiceProvider;
use RuntimeException;
use Swoole\Server;

class ProviderService
{
    /**
     * @var ServiceProvider[]
     */
    private array $serviceProviders;
    private array $bootedServiceProviders = [];

    public function __construct(array $providers = [])
    {
        $this->serviceProviders = $providers;
    }

    /**
     * bootstrap serviceProviders
     * @param Server|null $server
     * @param int|null $workerId
     * @throws \Mini\Contracts\Container\BindingResolutionException
     */
    public function bootstrap(?Server $server = null, ?int $workerId = null): void
    {
        $app = app();
        foreach ($this->serviceProviders as $serviceProvider) {
            if (!class_exists($serviceProvider)) {
                throw new RuntimeException('class ' . $serviceProvider . ' not exists.');
            }
            if (!($serviceProvider = new $serviceProvider($app)) instanceof ServiceProvider) {
                throw new RuntimeException($serviceProvider . ' should instanceof ' . ServiceProvider::class);
            }
            $serviceProvider->register($server, $workerId);
            $this->bootedServiceProviders[] = $serviceProvider;
        }
        foreach ($this->bootedServiceProviders as $bootedServiceProvider) {
            $bootedServiceProvider->boot($server, $workerId);
        }
    }

    /**
     * add serviceProvider
     * @param string $serviceProvider
     */
    public function addServiceProvider(string $serviceProvider): void
    {
        if (!new $serviceProvider instanceof ServiceProvider) {
            throw new RuntimeException($serviceProvider . ' should instanceof ' . ServiceProvider::class);
        }
        $this->serviceProviders[] = $serviceProvider;
    }

    /**
     * remove serviceProvider
     * @param string $serviceProvider
     */
    public function removeServiceProvider(string $serviceProvider): void
    {
        if (isset($this->serviceProviders[$serviceProvider])) {
            unset($this->serviceProviders[$serviceProvider]);
        }
    }

    /**
     * @return ServiceProvider[]
     */
    public function getServiceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * @return array
     */
    public function getBootedServiceProviders(): array
    {
        return $this->bootedServiceProviders;
    }
}