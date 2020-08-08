<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesIncludes
{
    /**
     * Compile the each statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileEach(string $expression): string
    {
        return "<?php echo \$__env->renderEach{$expression}; ?>";
    }

    /**
     * Compile the include statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileInclude(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->make({$expression}, \Mini\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    /**
     * Compile the include-if statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileIncludeIf(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, \Mini\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    /**
     * Compile the include-when statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileIncludeWhen(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderWhen($expression, \Mini\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    /**
     * Compile the include-unless statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileIncludeUnless(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderWhen(! $expression, \Mini\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    /**
     * Compile the include-first statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileIncludeFirst(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->first({$expression}, \Mini\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }
}
