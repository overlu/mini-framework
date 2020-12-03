<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Filesystem;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param  string|null  $name
     * @return \Mini\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null);
}
