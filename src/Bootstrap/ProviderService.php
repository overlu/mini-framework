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
     */
    public function bootstrap(?Server $server = null, ?int $workerId = null): void
    {
        $app = app();
        $booted = [];
        foreach ($this->serviceProviders as $serviceProvider) {
            if (!class_exists($serviceProvider)) {
                throw new RuntimeException('class ' . $serviceProvider . ' not exists.');
            }
            /**
             * @var $serviceProviderObj ServiceProvider
             */
            if (!($serviceProviderObj = new $serviceProvider($app, $server, $workerId)) instanceof ServiceProvider) {
                throw new RuntimeException($serviceProvider . ' should instanceof ' . ServiceProvider::class);
            }
            if ($this->serviceProviderWasNotBooted($serviceProvider)) {
                $booted[] = $serviceProviderObj;
                $serviceProviderObj->register();
                $this->bootedServiceProviders[] = $serviceProvider;
            }
        }
        foreach ($booted as $serviceProviderBooted) {
            /**
             * @var $serviceProviderBooted ServiceProvider
             */
            $serviceProviderBooted->boot();
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
        if ($this->hasServiceProvider($serviceProvider)) {
            unset($this->serviceProviders[$serviceProvider]);
        }
    }

    /**
     * @param string $serviceProvider
     * @return bool
     */
    public function hasServiceProvider(string $serviceProvider): bool
    {
        return isset(array_flip($this->serviceProviders)[$serviceProvider]);
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

    /**
     * @param string $serviceProvider
     * @return bool
     */
    public function serviceProviderWasBooted(string $serviceProvider): bool
    {
        return isset(array_flip($this->bootedServiceProviders)[$serviceProvider]);
    }

    /**
     * @param string $serviceProvider
     * @return bool
     */
    public function serviceProviderWasNotBooted(string $serviceProvider): bool
    {
        return !$this->serviceProviderWasBooted($serviceProvider);
    }
}