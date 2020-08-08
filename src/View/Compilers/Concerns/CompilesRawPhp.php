<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesRawPhp
{
    /**
     * Compile the raw PHP statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compilePhp(?string $expression): string
    {
        if ($expression) {
            return "<?php {$expression}; ?>";
        }

        return '@php';
    }

    /**
     * Compile the unset statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileUnset(string $expression): string
    {
        return "<?php unset{$expression}; ?>";
    }
}
