<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Foundation;

interface CachesConfiguration
{
    /**
     * Determine if the application configuration is cached.
     *
     * @return bool
     */
    public function configurationIsCached(): bool;

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath(): string;

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath(): string;
}
