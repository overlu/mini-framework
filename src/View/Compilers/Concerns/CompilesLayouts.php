<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

use Mini\View\Factory as ViewFactory;

trait CompilesLayouts
{
    /**
     * The name of the last section that was started.
     *
     * @var string
     */
    protected string $lastSection;

    /**
     * Compile the extends statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileExtends(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        $echo = "<?php echo \$__env->make({$expression}, \Mini\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";

        $this->footer[] = $echo;

        return '';
    }

    /**
     * Compile the section statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileSection(string $expression): string
    {
        $this->lastSection = trim($expression, "()'\" ");

        return "<?php \$__env->startSection{$expression}; ?>";
    }

    /**
     * Replace the @parent directive to a placeholder.
     *
     * @return string
     */
    protected function compileParent(): string
    {
        return ViewFactory::parentPlaceholder($this->lastSection ?: '');
    }

    /**
     * Compile the yield statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileYield(string $expression): string
    {
        return "<?php echo \$__env->yieldContent{$expression}; ?>";
    }

    /**
     * Compile the show statements into valid PHP.
     *
     * @return string
     */
    protected function compileShow(): string
    {
        return '<?php echo $__env->yieldSection(); ?>';
    }

    /**
     * Compile the append statements into valid PHP.
     *
     * @return string
     */
    protected function compileAppend(): string
    {
        return '<?php $__env->appendSection(); ?>';
    }

    /**
     * Compile the overwrite statements into valid PHP.
     *
     * @return string
     */
    protected function compileOverwrite(): string
    {
        return '<?php $__env->stopSection(true); ?>';
    }

    /**
     * Compile the stop statements into valid PHP.
     *
     * @return string
     */
    protected function compileStop(): string
    {
        return '<?php $__env->stopSection(); ?>';
    }

    /**
     * Compile the end-section statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndsection(): string
    {
        return '<?php $__env->stopSection(); ?>';
    }
}
