<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Bootstrap;

use Mini\Container\Container;
use Mini\Service\AbstractServiceProvider;
use RuntimeException;
use Swoole\Server;

class ProviderService
{
    /**
     * @var AbstractServiceProvider[]
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
        $booted = [];
        foreach ($this->serviceProviders as $serviceProvider) {
            if (!class_exists($serviceProvider)) {
                throw new RuntimeException('class ' . $serviceProvider . ' not exists.');
            }
            /**
             * @var $serviceProviderObj AbstractServiceProvider
             */
            if (!($serviceProviderObj = new $serviceProvider(Container::getInstance(), $server, $workerId)) instanceof AbstractServiceProvider) {
                throw new RuntimeException($serviceProvider . ' should instanceof ' . AbstractServiceProvider::class);
            }
            if ($this->serviceProviderWasNotBooted($serviceProvider)) {
                $booted[] = $serviceProviderObj;
                $serviceProviderObj->register();
                $this->bootedServiceProviders[] = $serviceProvider;
            }
        }
        foreach ($booted as $serviceProviderBooted) {
            /**
             * @var $serviceProviderBooted AbstractServiceProvider
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
        if (!new $serviceProvider instanceof AbstractServiceProvider) {
            throw new RuntimeException($serviceProvider . ' should instanceof ' . AbstractServiceProvider::class);
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
     * @return AbstractServiceProvider[]
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