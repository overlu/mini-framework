<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Singleton;
use Mini\Support\Arr;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class Translate
{
    protected array $translation = [];
    protected string $locate = 'en';
    protected Translator $translator;

    public function __construct()
    {
        $this->locate = config('app.locale', config('app.fallback_locale', 'en'));
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
        return empty($parameters) ? (Arr::get($this->translation[$locale ?: $this->locate], $key) ?? $key) : $this->trans($key, $parameters, $domain, $locale);
    }

    /**
     * @param string|null $key
     * @param string|null $default
     * @param string|null $locale
     * @return string
     */
    public function getOrDefault(?string $key, ?string $default = null, ?string $locale = null): string
    {
        return Arr::get($this->translation[$locale ?: $this->locate], $key) ?? ($default ?: $key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->translation[$locale ?: $this->locate], $key);
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
        foreach ($parameters as $key => $parameter) {
            $parameters[':' . $key] = $parameter;
            unset($parameters[$key]);
        }
        return $this->translator->trans($key, $parameters, $domain, $locale);
    }

    /**
     * @return mixed|string|void
     */
    public function getLocale()
    {
        return $this->locate;
    }
}