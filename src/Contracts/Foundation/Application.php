<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Foundation;

use Mini\Contracts\Container\Container;
use Mini\Service\AbstractServiceProvider;

interface Application extends Container
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get the base path of the Laravel installation.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string;

    /**
     * Get the path to the bootstrap directory.
     *
     * @param string $path Optionally, a path to append to the bootstrap path
     * @return string
     */
    public function bootstrapPath(string $path = ''): string;

    /**
     * Get the path to the application configuration files.
     *
     * @param string $path Optionally, a path to append to the config path
     * @return string
     */
    public function configPath(string $path = ''): string;

    /**
     * Get the path to the database directory.
     *
     * @param string $path Optionally, a path to append to the database path
     * @return string
     */
    public function databasePath(string $path = ''): string;

    /**
     * Get the path to the resources directory.
     *
     * @param string $path
     * @return string
     */
    public function resourcePath(string $path = ''): string;

    /**
     * Get the path to the storage directory.
     *
     * @return string
     */
    public function storagePath(): string;

    /**
     * Get or check the current application environment.
     *
     * @param string|array $environments
     * @return string
     */
    public function environment(...$environments): string;

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole(): bool;

    /**
     * Determine if the application is running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests(): bool;

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance(): bool;

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders(): void;

    /**
     * Register a service provider with the application.
     *
     * @param AbstractServiceProvider|string $provider
     * @param bool $force
     * @return AbstractServiceProvider
     */
    public function register(AbstractServiceProvider|string $provider, bool $force = false);

    /**
     * Register a deferred provider and service.
     *
     * @param string $provider
     * @param string|null $service
     * @return void
     */
    public function registerDeferredProvider(string $provider, string $service = null): void;

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param string $provider
     * @return AbstractServiceProvider
     */
    public function resolveProvider(string $provider): AbstractServiceProvider;

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Register a new boot listener.
     *
     * @param callable $callback
     * @return void
     */
    public function booting(callable $callback): void;

    /**
     * Register a new "booted" listener.
     *
     * @param callable $callback
     * @return void
     */
    public function booted(callable $callback): void;

    /**
     * Run the given array of bootstrap classes.
     *
     * @param array $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers): void;

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace(): string;

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param string|AbstractServiceProvider $provider
     * @return array
     */
    public function getProviders(AbstractServiceProvider|string $provider): array;

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped(): bool;

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders(): void;

    /**
     * Set the current application locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale(string $locale): void;

    /**
     * Determine if middleware has been disabled for the application.
     *
     * @return bool
     */
    public function shouldSkipMiddleware(): bool;

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate(): void;
}
