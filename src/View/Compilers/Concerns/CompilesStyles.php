<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesStyles
{
    /**
     * Compile the conditional style statement into valid PHP.
     *
     * @param string|null $expression
     * @return string
     */
    protected function compileStyle(string $expression = null): string
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "style=\"<?php echo \Mini\Support\Arr::toCssStyles{$expression} ?>\"";
    }
}
