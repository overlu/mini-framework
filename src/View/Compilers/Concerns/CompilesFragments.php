<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesFragments
{
    /**
     * The last compiled fragment.
     *
     * @var string
     */
    protected string $lastFragment;

    /**
     * Compile the fragment statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileFragment(string $expression): string
    {
        $this->lastFragment = trim($expression, "()'\" ");

        return "<?php \$__env->startFragment{$expression}; ?>";
    }

    /**
     * Compile the end-fragment statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndfragment(): string
    {
        return '<?php echo $__env->stopFragment(); ?>';
    }
}
