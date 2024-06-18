<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Foundation;

use Mini\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get or check the current application environment.
     * @return string
     */
    public function environment(): string;

    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace(): string;

    /**
     * Set the current application locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale(string $locale): void;
}
