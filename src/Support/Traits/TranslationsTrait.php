<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

trait TranslationsTrait
{

    /** @var array */
    protected array $translations = [];

    /**
     * Given $key and $translation to set translation
     * @param mixed $key
     * @param mixed $translation
     * @return void
     */
    public function setTranslation(string $key, string $translation): void
    {
        $this->translations[$key] = $translation;
    }

    /**
     * Given $translations and set multiple translations
     * @param array $translations
     * @return void
     */
    public function setTranslations(array $translations): void
    {
        $this->translations = array_merge($this->translations, $translations);
    }

    /**
     * Given translation from given $key
     * @param string $key
     * @return string
     */
    public function getTranslation(string $key): string
    {
        return array_key_exists($key, $this->translations) ? $this->translations[$key] : $key;
    }

    /**
     * Get all $translations
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}
