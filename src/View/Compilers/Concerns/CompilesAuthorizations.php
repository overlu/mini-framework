<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesAuthorizations
{
    /**
     * Compile the can statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileCan(?string $expression): string
    {
        return "<?php if (app(\Mini\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the cannot statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileCannot(?string $expression): string
    {
        return "<?php if (app(\Mini\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the canany statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileCanany(?string $expression): string
    {
        return "<?php if (app(\Mini\\Contracts\\Auth\\Access\\Gate::class)->any{$expression}): ?>";
    }

    /**
     * Compile the else-can statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileElsecan(?string $expression): string
    {
        return "<?php elseif (app(\Mini\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the else-cannot statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileElsecannot(?string $expression): string
    {
        return "<?php elseif (app(\Mini\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the else-canany statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileElsecanany(?string $expression): string
    {
        return "<?php elseif (app(\Mini\\Contracts\\Auth\\Access\\Gate::class)->any{$expression}): ?>";
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcan(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-cannot statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcannot(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-canany statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcanany(): string
    {
        return '<?php endif; ?>';
    }
}
