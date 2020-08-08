<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers;

use Mini\Support\Filesystem;
use InvalidArgumentException;

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
     * @var string
     */
    protected string $cachePath;

    /**
     * Create a new compiler instance.
     *
     * @param Filesystem $files
     * @param string $cachePath
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Filesystem $files, string $cachePath)
    {
        if (!$cachePath) {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }

        $this->files = $files;
        $this->cachePath = $cachePath;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param string $path
     * @return string
     */
    public function getCompiledPath(string $path): string
    {
        return $this->cachePath . '/' . sha1($path) . '.php';
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param string $path
     * @return bool
     */
    public function isExpired(string $path): bool
    {
        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        if (!$this->files->exists($compiled)) {
            return true;
        }

        return $this->files->lastModified($path) >=
            $this->files->lastModified($compiled);
    }
}
