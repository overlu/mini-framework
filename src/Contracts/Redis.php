<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

/**
 * @mixin \Redis
 */
interface Redis
{
    /**
     * @param string $key
     * @return \Redis
     */
    public function getConnection(string $key = 'default'): \Redis;
}
