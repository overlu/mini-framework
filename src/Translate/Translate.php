<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Singleton;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class Translate
{
    use Singleton;

    protected array $translation = [];
    protected string $locate = 'en';
    protected Translator $translator;

    private function __construct()
    {
        $this->locate = config('mini.language', 'en');
        $this->translator = new Translator($this->locate);
    }

    public function initialize(): void
    {
        if (empty($this->translation)) {
            $directory = resource_path('lang');
            $langFiles = app('files')->allFiles($directory);
            foreach ($langFiles as $langFile) {
                $this->translation[$langFile->getRelativePath()] = require $langFile->getRealPath();
                $this->translator->addResource('array', $this->translation[$langFile->getRelativePath()], $langFile->getRelativePath());
            }
            $this->translator->addLoader('array', new ArrayLoader());
        }
    }

    public function get(?string $id = null, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return empty($parameters) ? ($this->translation[$this->locate][$id] ?? $id) : $this->trans($id, $parameters, $domain, $locale);
    }

    public function trans(?string $id = null, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}