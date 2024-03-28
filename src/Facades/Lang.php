<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class Lang
 * @package Mini\Facades
 * @method static bool has(string $key)
 * @method static string get(null|string $key = null, array $parameters = [], null|string $domain = null, null|string $locale = null)
 * @method static string getOrDefault(null|string $key = null, null|string $default = null, null|string $locale = null)
 * @method static string trans(null|string $key = null, array $parameters = [], null|string $domain = null, null|string $locale = null)
 * @method static string getLocale()
 * @method static void resetLocale()
 * @method static void setLocale(string $locate)
 * @see \Mini\Translate\Translate
 */
class Lang extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'translate';
    }
}