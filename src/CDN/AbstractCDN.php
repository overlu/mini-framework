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
     * @return string
     */
    public function sign(string $url, DateTime|int $expiration = null): string;

    /**
     * @param string $url
     * @return string
     */
    public function url(string $url): string;
}
