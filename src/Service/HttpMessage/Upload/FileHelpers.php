<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\HttpMessage\Upload;

use Mini\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * @var string|null
     */
    protected ?string $hashName = null;

    /**
     * Get the fully qualified path to the file.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->getRealPath();
    }

    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension(): string
    {
        return $this->getExtension();
    }

    /**
     * Get a filename for the file.
     *
     * @param string|null $path
     * @return string
     */
    public function hashName(string $path = null): string
    {
        if ($path) {
            $path = rtrim($path, '/') . '/';
        }

        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        if ($extension = $this->getExtension()) {
            $extension = '.' . $extension;
        }

        return $path . $hash . $extension;
    }
}
