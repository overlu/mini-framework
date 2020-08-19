<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Translate;

use Mini\Singleton;

class Translate
{
    use Singleton;

    protected array $translation = [];

    public function initialize(): void
    {
        if (empty($this->translation)) {
            $directory = resource_path('lang');
            $langFiles = app('files')->allFiles($directory);
            foreach ($langFiles as $langFile) {
                $this->translation[$langFile->getRelativePath()] = require $langFile->getRealPath();
            }
        }
    }

    public function get(string $key): string
    {
        return $this->translation[config('mini.language', 'en')][$key] ?? $key;
    }
}