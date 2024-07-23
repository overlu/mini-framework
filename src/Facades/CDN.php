<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\CDN\AbstractCDN;

/**
 * Class CDN
 * @method static string url(string $url)
 * @method static string sign(string $url, \DateTime|int $expiration = null)
 * @package Mini\Facades
 */
class CDN extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cdn.' . config('cdn.default', 'cloudfront');
    }

    protected static function driver(string $driver): AbstractCDN
    {
        return app('cdn')->driver($driver);
    }
}
