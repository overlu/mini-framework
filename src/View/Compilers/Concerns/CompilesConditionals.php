<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesConditionals
{
    /**
     * Identifier for the first case in switch statement.
     *
     * @var bool
     */
    protected bool $firstCaseInSwitch = true;

    /**
     * Compile the if-auth statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileAuth(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php if(auth()->guard{$guard}->check()): ?>";
    }

    /**
     * Compile the else-auth statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileElseAuth(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php elseif(auth()->guard{$guard}->check()): ?>";
    }

    /**
     * Compile the end-auth statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndAuth(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the env statements into valid PHP.
     *
     * @param string|null $environments
     * @return string
     */
    protected function compileEnv(?string $environments): string
    {
        return "<?php if(app()->environment{$environments}): ?>";
    }

    /**
     * Compile the end-env statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndEnv(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the production statements into valid PHP.
     *
     * @return string
     */
    protected function compileProduction(): string
    {
        return "<?php if(app()->environment('production')): ?>";
    }

    /**
     * Compile the end-production statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndProduction(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the if-guest statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileGuest(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php if(auth()->guard{$guard}->guest()): ?>";
    }

    /**
     * Compile the else-guest statements into valid PHP.
     *
     * @param string|null $guard
     * @return string
     */
    protected function compileElseGuest(?string $guard = null): string
    {
        $guard = is_null($guard) ? '()' : $guard;

        return "<?php elseif(auth()->guard{$guard}->guest()): ?>";
    }

    /**
     * Compile the end-guest statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndGuest(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the has-section statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileHasSection(?string $expression): string
    {
        return "<?php if (! empty(trim(\$__env->yieldContent{$expression}))): ?>";
    }

    /**
     * Compile the section-missing statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileSectionMissing(?string $expression): string
    {
        return "<?php if (empty(trim(\$__env->yieldContent{$expression}))): ?>";
    }

    /**
     * Compile the if statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileIf(?string $expression): string
    {
        return "<?php if{$expression}: ?>";
    }

    /**
     * Compile the unless statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileUnless(?string $expression): string
    {
        return "<?php if (! {$expression}): ?>";
    }

    /**
     * Compile the else-if statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileElseif(?string $expression): string
    {
        return "<?php elseif{$expression}: ?>";
    }

    /**
     * Compile the else statements into valid PHP.
     *
     * @return string
     */
    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    /**
     * Compile the end-if statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndif(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-unless statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndunless(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the if-isset statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileIsset(?string $expression): string
    {
        return "<?php if(isset{$expression}): ?>";
    }

    /**
     * Compile the end-isset statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndIsset(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the switch statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileSwitch(?string $expression): string
    {
        $this->firstCaseInSwitch = true;

        return "<?php switch{$expression}:";
    }

    /**
     * Compile the case statements into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileCase(?string $expression): string
    {
        if ($this->firstCaseInSwitch) {
            $this->firstCaseInSwitch = false;

            return "case {$expression}: ?>";
        }

        return "<?php case {$expression}: ?>";
    }

    /**
     * Compile the default statements in switch case into valid PHP.
     *
     * @return string
     */
    protected function compileDefault(): string
    {
        return '<?php default: ?>';
    }

    /**
     * Compile the end switch statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndSwitch(): string
    {
        return '<?php endswitch; ?>';
    }
}
