<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\CDN;

use DateTime;

interface AbstractCDN
{

    /**
     * @param string $url
     * @param DateTime|int|null $expiration
     * @param mixed|null $policy
     * @return string
     */
    public function sign(string $url, DateTime|int $expiration = null, mixed $policy = null): string;

    /**
     * @param string $url
     * @param mixed|null $policy
     * @return string
     */
    public function url(string $url, mixed $policy = null): string;
}
