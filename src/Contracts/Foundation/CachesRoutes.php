<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Foundation;

interface CachesRoutes
{
    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached(): bool;

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath(): string;
}
