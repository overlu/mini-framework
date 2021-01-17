<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

use Mini\Service\HttpServer\UrlGenerator;

/**
 * @method static \Mini\Service\HttpMessage\Uri\Uri full()
 * @method static \Mini\Service\HttpMessage\Uri\Uri current()
 * @method static \Mini\Service\HttpMessage\Uri\Uri path(string $path = '')
 * @method static \Mini\Service\HttpMessage\Uri\Uri previous()
 * @method static \Mini\Service\HttpMessage\Uri\Uri secure(string $path = '')
 * @method static \Mini\Service\HttpMessage\Uri\Uri make(string $path = '', array $params = [], string $fragment = '')
 *
 * @see UrlGenerator
 */
class Url extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return UrlGenerator::class;
    }
}
