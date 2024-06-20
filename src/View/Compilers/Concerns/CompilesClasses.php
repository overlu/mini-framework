<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesClasses
{
    /**
     * Compile the conditional class statement into valid PHP.
     * @param string|null $expression
     * @return string
     */
    protected function compileClass(string $expression = null): string
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "class=\"<?php echo \Mini\Support\Arr::toCssClasses{$expression}; ?>\"";
    }
}
