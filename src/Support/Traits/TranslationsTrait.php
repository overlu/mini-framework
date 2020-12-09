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
     * @return static
     */
    public function setTranslation(string $key, string $translation): self
    {
        $this->translations[$key] = $translation;
        return $this;
    }

    /**
     * Given $translations and set multiple translations
     * @param array $translations
     * @return static
     */
    public function setTranslations(array $translations): self
    {
        $this->translations = $translations;
        return $this;
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

    /**
     * clear all $translations
     */
    public function clearTranslations(): void
    {
        $this->translations = [];
    }
}
