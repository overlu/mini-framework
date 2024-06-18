<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Translation;

interface Translator
{
    /**
     * @param string|null $key
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function get(?string $key = null, array $parameters = [], ?string $domain = null, ?string $locale = null): string;

    /**
     * @param string|null $key
     * @param string|null $default
     * @param string|null $locale
     * @return string
     */
    public function getOrDefault(?string $key, ?string $default = null, ?string $locale = null): string;

    /**
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool;

    /**
     * @param string|null $key
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function trans(?string $key = null, array $parameters = [], ?string $domain = null, ?string $locale = null): string;

    /**
     * @return string
     */
    public function getLocale(): string;

    /**
     * @param string $locate
     * @return void
     */
    public function setLocale(string $locate): void;

    /**
     * @return void
     */
    public function resetLocale(): void;

    public function getFallbackLocale(): string;

    /**
     * @param string $locate
     * @return void
     */
    public function setFallbackLocale(string $locate): void;

    public function resetFallbackLocale(): void;
}
