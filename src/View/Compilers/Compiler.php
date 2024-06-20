<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers;

use Mini\Filesystem\Filesystem;
use InvalidArgumentException;
use Mini\Support\Str;

abstract class Compiler
{
    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Get the cache path for the compiled views.
     *
     * @var string|null
     */
    protected ?string $cachePath = null;

    /**
     * The base path that should be removed from paths before hashing.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Determines if compiled views should be cached.
     *
     * @var bool
     */
    protected bool $shouldCache;

    /**
     * The compiled view file extension.
     *
     * @var string
     */
    protected string $compiledExtension = 'php';

    /**
     * Create a new compiler instance.
     *
     * @param Filesystem $files
     * @param string $cachePath
     * @param string $basePath
     * @param bool $shouldCache
     * @param string $compiledExtension
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        Filesystem $files,
        string     $cachePath = null,
        string     $basePath = '',
        bool       $shouldCache = true,
        string     $compiledExtension = 'php')
    {
        if (!$cachePath) {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }

        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->basePath = $basePath;
        $this->shouldCache = $shouldCache;
        $this->compiledExtension = $compiledExtension;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param string $path
     * @return string
     */
    public function getCompiledPath(string $path): string
    {
        return $this->cachePath . '/' . hash('xxh128', 'v2' . Str::after($path, $this->basePath)) . '.' . $this->compiledExtension;
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param string $path
     * @return bool
     * @throws \ErrorException
     */
    public function isExpired(string $path): bool
    {
        if (!$this->shouldCache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        if (!$this->files->exists($compiled)) {
            return true;
        }

        try {
            return $this->files->lastModified($path) >=
                $this->files->lastModified($compiled);
        } catch (\ErrorException $exception) {
            if (!$this->files->exists($compiled)) {
                return true;
            }

            throw $exception;
        }
    }

    /**
     * Create the compiled file directory if necessary.
     *
     * @param string $path
     * @return void
     */
    protected function ensureCompiledDirectoryExists(string $path): void
    {
        if (!$this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }
}
