<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesStacks
{
    /**
     * Compile the stack statements into the content.
     *
     * @param string $expression
     * @return string
     */
    protected function compileStack(string $expression): string
    {
        return "<?php echo \$__env->yieldPushContent{$expression}; ?>";
    }

    /**
     * Compile the push statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compilePush(string $expression): string
    {
        return "<?php \$__env->startPush{$expression}; ?>";
    }

    /**
     * Compile the end-push statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndpush(): string
    {
        return '<?php $__env->stopPush(); ?>';
    }

    /**
     * Compile the prepend statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compilePrepend(string $expression): string
    {
        return "<?php \$__env->startPrepend{$expression}; ?>";
    }

    /**
     * Compile the end-prepend statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndprepend(): string
    {
        return '<?php $__env->stopPrepend(); ?>';
    }
}
