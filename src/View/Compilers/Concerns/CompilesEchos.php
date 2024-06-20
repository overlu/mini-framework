<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

use Closure;
use Mini\Support\Str;

trait CompilesEchos
{
    /**
     * Custom rendering callbacks for stringable objects.
     *
     * @var array
     */
    protected array $echoHandlers = [];

    /**
     * Add a handler to be executed before echoing a given class.
     *
     * @param callable|string $class
     * @param callable|null $handler
     * @return void
     */
    public function stringable(callable|string $class, callable $handler = null): void
    {
        if ($class instanceof Closure) {
            [$class, $handler] = [$this->firstClosureParameterType($class), $class];
        }

        $this->echoHandlers[$class] = $handler;
    }

    /**
     * Compile Blade echos into valid PHP.
     *
     * @param string|null $value
     * @return string
     */
    public function compileEchos(?string $value): string
    {
        foreach ($this->getEchoMethods() as $method) {
            $value = $this->$method($value);
        }

        return $value;
    }

    /**
     * Get the echo methods in the proper order for compilation.
     *
     * @return array
     */
    protected function getEchoMethods(): array
    {
        return [
            'compileRawEchos',
            'compileEscapedEchos',
            'compileRegularEchos',
        ];
    }

    /**
     * Compile the "raw" echo statements.
     *
     * @param string $value
     * @return string
     */
    protected function compileRawEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

        $callback = static function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

            return $matches[1] ? substr($matches[0], 1) : "<?php echo {$matches[2]}; ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the "regular" echo statements.
     *
     * @param string $value
     * @return string
     */
    protected function compileRegularEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

            $wrapped = sprintf($this->echoFormat, $this->wrapInEchoHandler($matches[2]));

            return $matches[1] ? substr($matches[0], 1) : "<?php echo {$wrapped}; ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the escaped echo statements.
     *
     * @param string $value
     * @return string
     */
    protected function compileEscapedEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        $callback = static function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

            return $matches[1] ? $matches[0] : "<?php echo e({$matches[2]}); ?>{$whitespace}";
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Add an instance of the blade echo handler to the start of the compiled string.
     *
     * @param string $result
     * @return string
     */
    protected function addBladeCompilerVariable(string $result): string
    {
        return "<?php \$__bladeCompiler = app('blade.compiler'); ?>" . $result;
    }

    /**
     * Wrap the echoable value in an echo handler if applicable.
     *
     * @param string $value
     * @return string
     */
    protected function wrapInEchoHandler(string $value)
    {
        $value = Str::of($value)
            ->trim()
            ->when(str_ends_with($value, ';'), function ($str) {
                return $str->beforeLast(';');
            });

        return empty($this->echoHandlers) ? $value : '$__bladeCompiler->applyEchoHandler(' . $value . ')';
    }

    /**
     * Apply the echo handler for the value if it exists.
     *
     * @param string|mixed $value
     * @return string
     */
    public function applyEchoHandler(mixed $value): string
    {
        if (is_object($value) && isset($this->echoHandlers[get_class($value)])) {
            return call_user_func($this->echoHandlers[get_class($value)], $value);
        }

        return $value;
    }
}
