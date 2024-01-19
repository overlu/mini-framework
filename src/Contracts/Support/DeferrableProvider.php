<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Support;

interface DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array;
}
