<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesInjections
{
    /**
     * Compile the inject statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileInject(string $expression): string
    {
        $segments = explode(',', preg_replace("/[\(\)]/", '', $expression));

        $variable = trim($segments[0], " '\"");

        $service = trim($segments[1]);

        return "<?php \${$variable} = app({$service}); ?>";
    }
}
