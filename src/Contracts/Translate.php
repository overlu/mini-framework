<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

/**
 * Class Lang
 * @package Mini\Facades
 * @method bool has(string $key)
 * @method string get(null|string $key = null, array $parameters = [], null|string $domain = null, null|string $locale = null)
 * @method string getOrDefault(null|string $key = null, null|string $default = null, null|string $locale = null)
 * @method string trans(null|string $key = null, array $parameters = [], null|string $domain = null, null|string $locale = null)
 * @method string getLocale()
 * @see \Mini\Translate\Translate
 */
interface Translate
{

}
