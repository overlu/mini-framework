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
 * @method static string url(string $url, mixed $policy = null)
 * @method static string sign(string $url, \DateTime|int $expiration = null, mixed $policy = null)
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
        return 'cdn.drivers.' . config('cdn.default', 'cloudfront');
    }

    /**
     * @param string $driver
     * @return AbstractCDN
     */
    public static function driver(string $driver): AbstractCDN
    {
        return app('cdn')->driver($driver);
    }

    /**
     * @param string $driver
     * @param mixed $closure
     * @return void
     */
    public static function extend(string $driver, mixed $closure): void
    {
        app('cdn')->extend($driver, $closure);
    }
}
