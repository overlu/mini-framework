<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Compilers\Concerns;

use Mini\Support\Str;
use Mini\View\ComponentAttributeBag;

trait CompilesComponents
{
    /**
     * The component name hash stack.
     *
     * @var array
     */
    protected static array $componentHashStack = [];

    /**
     * Compile the component statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileComponent(string $expression): string
    {
        [$component, $alias, $data] = strpos($expression, ',') !== false
            ? array_map('trim', explode(',', trim($expression, '()'), 3)) + ['', '', '']
            : [trim($expression, '()'), '', ''];

        $component = trim($component, '\'"');

        $hash = static::newComponentHash($component);

        if (Str::contains($component, ['::class', '\\'])) {
            return static::compileClassComponentOpening($component, $alias, $data, $hash);
        }

        return "<?php \$__env->startComponent{$expression}; ?>";
    }

    /**
     * Get a new component hash for a component name.
     *
     * @param string $component
     * @return string
     */
    public static function newComponentHash(string $component): string
    {
        static::$componentHashStack[] = $hash = sha1($component);

        return $hash;
    }

    /**
     * Compile a class component opening.
     *
     * @param string $component
     * @param string $alias
     * @param string $data
     * @param string $hash
     * @return string
     */
    public static function compileClassComponentOpening(string $component, string $alias, string $data, string $hash): string
    {
        return implode("\n", [
            '<?php if (isset($component)) { $__componentOriginal' . $hash . ' = $component; } ?>',
            '<?php $component = $__env->getContainer()->make(' . Str::finish($component, '::class') . ', ' . ($data ?: '[]') . '); ?>',
            '<?php $component->withName(' . $alias . '); ?>',
            '<?php if ($component->shouldRender()): ?>',
            '<?php $__env->startComponent($component->resolveView(), $component->data()); ?>',
        ]);
    }

    /**
     * Compile the end-component statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndComponent(): string
    {
        $hash = array_pop(static::$componentHashStack);

        return implode("\n", [
            '<?php if (isset($__componentOriginal' . $hash . ')): ?>',
            '<?php $component = $__componentOriginal' . $hash . '; ?>',
            '<?php unset($__componentOriginal' . $hash . '); ?>',
            '<?php endif; ?>',
            '<?php echo $__env->renderComponent(); ?>',
        ]);
    }

    /**
     * Compile the end-component statements into valid PHP.
     *
     * @return string
     */
    public function compileEndComponentClass(): string
    {
        return static::compileEndComponent() . "\n" . implode("\n", [
                '<?php endif; ?>',
            ]);
    }

    /**
     * Compile the slot statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileSlot(?string $expression): string
    {
        return "<?php \$__env->slot{$expression}; ?>";
    }

    /**
     * Compile the end-slot statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndSlot(): string
    {
        return '<?php $__env->endSlot(); ?>';
    }

    /**
     * Compile the component-first statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileComponentFirst(?string $expression): string
    {
        return "<?php \$__env->startComponentFirst{$expression}; ?>";
    }

    /**
     * Compile the end-component-first statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndComponentFirst(): string
    {
        return $this->compileEndComponent();
    }

    /**
     * Compile the prop statement into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileProps(?string $expression): string
    {
        return "<?php \$attributes = \$attributes->exceptProps{$expression}; ?>
<?php foreach (array_filter({$expression}, 'is_string', ARRAY_FILTER_USE_KEY) as \$__key => \$__value) {
    \$\$__key = \$\$__key ?? \$__value;
} ?>
<?php \$__defined_vars = get_defined_vars(); ?>
<?php foreach (\$attributes as \$__key => \$__value) {
    if (array_key_exists(\$__key, \$__defined_vars)) unset(\$\$__key);
} ?>
<?php unset(\$__defined_vars); ?>";
    }

    /**
     * Sanitize the given component attribute value.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function sanitizeComponentAttribute($value)
    {
        return is_string($value) ||
        (is_object($value) && !$value instanceof ComponentAttributeBag && method_exists($value, '__toString'))
            ? e($value)
            : $value;
    }
}
