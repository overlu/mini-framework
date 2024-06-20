<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers;

interface CompilerInterface
{
    /**
     * Get the path to the compiled version of a view.
     *
     * @param string $path
     * @return string
     */
    public function getCompiledPath(string $path): string;

    /**
     * Determine if the given view is expired.
     *
     * @param string $path
     * @return bool
     */
    public function isExpired(string $path): bool;

    /**
     * Compile the view at the given path.
     *
     * @param string|null $path
     * @return void
     */
    public function compile(string $path = null): void;
}
