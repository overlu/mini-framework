<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class MiniDB
 * @method static void addServiceProvider(string $serviceProvider)
 * @method static void removeServiceProvider(string $serviceProvider)
 * @method static bool hasServiceProvider(string $serviceProvider)
 * @method static bool serviceProviderWasBooted(string $serviceProvider)
 * @method static bool serviceProviderWasNotBooted(string $serviceProvider)
 * @method static array getServiceProviders()
 * @method static array getBootedServiceProviders()
 * @package Mini\Facades
 * @see \Mini\Bootstrap\ProviderService
 */
class Provider extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'providers';
    }
}