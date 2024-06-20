<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

trait CompilesSessions
{
    /**
     * Compile the session statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileSession(string $expression): string
    {
        $expression = $this->stripParentheses($expression);

        return '<?php $__sessionArgs = [' . $expression . '];
if (session()->has($__sessionArgs[0])) :
if (isset($value)) { $__sessionPrevious[] = $value; }
$value = session()->get($__sessionArgs[0]); ?>';
    }

    /**
     * Compile the endsession statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileEndsession(string $expression): string
    {
        return '<?php unset($value);
if (isset($__sessionPrevious) && !empty($__sessionPrevious)) { $value = array_pop($__sessionPrevious); }
if (isset($__sessionPrevious) && empty($__sessionPrevious)) { unset($__sessionPrevious); }
endif;
unset($__sessionArgs); ?>';
    }
}
