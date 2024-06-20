<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

use Mini\Support\Js;

trait CompilesJs
{
    /**
     * Compile the "@js" directive into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileJs(string $expression): string
    {
        return sprintf(
            "<?php echo \%s::from(%s)->toHtml() ?>",
            Js::class, $this->stripParentheses($expression)
        );
    }
}
