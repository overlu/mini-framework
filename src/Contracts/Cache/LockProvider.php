<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Cache;

interface LockProvider
{
    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Mini\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null);

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Mini\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner);
}
