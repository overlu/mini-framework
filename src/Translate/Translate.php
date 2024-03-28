<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Support\Arr;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class Translate implements \Mini\Contracts\Translate
{
    protected array $translation = [];
    protected string $locate = '';
    protected string $fallback_locale = 'en';
    protected Translator $translator;

    public function __construct()
    {
        $this->locate = config('app.locale', '');
        $this->fallback_locale = config('app.fallback_locale', 'en');
        $this->translator = new Translator($this->locate);
        $this->initialize();
    }

    public function initialize(): void
    {
        if (empty($this->translation)) {
            $directory = resource_path('lang');
            $langFiles = app('files')->allFiles($directory);
            foreach ($langFiles as $langFile) {
                $lang = $langFile->getRelativePath();
                $this->translation[$lang][$langFile->getFilenameWithoutExtension()] = require $langFile->getRealPath();
                $this->translator->addResource('array', $this->translation[$lang], $lang);
            }
            $this->translator->addLoader('array', new ArrayLoader());
        }
    }

    /**
     * @param string|null $key
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function get(?string $key = null, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if (empty($parameters)) {
            return Arr::get($this->translation[$locale ?: $this->locate] ?? [], $key, Arr::get($this->translation[$this->fallback_locale] ?? [], $key, $key));
        }
        return $this->trans($key, $parameters, $domain, $locale);
    }

    /**
     * @param string|null $key
     * @param string|null $default
     * @param string|null $locale
     * @return string
     */
    public function getOrDefault(?string $key, ?string $default = null, ?string $locale = null): string
    {
        return Arr::get($this->translation[$locale ?: $this->locate] ?? [], $key, ($default ?: $key));
    }

    /**
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool
    {
        return Arr::has($this->translation[$locale ?: $this->locate] ?? [], $key);
    }

    /**
     * @param string|null $key
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function trans(?string $key = null, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        foreach ($parameters as $k => $parameter) {
            $parameters[':' . $k] = $parameter;
            unset($parameters[$k]);
        }
        return $this->translator->trans($key, $parameters, $domain, $locale ?: $this->locate);
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locate;
    }

    /**
     * @param string $locate
     * @return void
     */
    public function setLocale(string $locate): void
    {
        $this->locate = $locate;
    }

    /**
     * @return void
     */
    public function resetLocale(): void
    {
        $this->locate = config('app.locale', '');
    }
}