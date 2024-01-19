<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

use Closure;
use Mini\Container\Container;

trait Localizable
{
    /**
     * Run the callback with the given locale.
     *
     * @param string $locale
     * @param Closure $callback
     * @return mixed
     */
    public function withLocale(string $locale, Closure $callback): mixed
    {
        if (! $locale) {
            return $callback();
        }

        $app = Container::getInstance();

        $original = $app->getLocale();

        try {
            $app->setLocale($locale);

            return $callback();
        } finally {
            $app->setLocale($original);
        }
    }
}
