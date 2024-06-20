<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Support\Str;
use Mini\View\Compilers\ComponentTagCompiler;

class DynamicComponent extends Component
{
    /**
     * The name of the component.
     *
     * @var string
     */
    public string $component;

    /**
     * The component tag compiler instance.
     * @var mixed
     */
    protected static mixed $compiler;

    /**
     * The cached component classes.
     *
     * @var array
     */
    protected static array $componentClasses = [];

    /**
     * The cached binding keys for component classes.
     *
     * @var array
     */
    protected static array $bindings = [];

    /**
     * Create a new component instance.
     *
     * @param string $component
     */
    public function __construct(string $component)
    {
        $this->component = $component;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        $template = <<<'EOF'
<?php extract(collect($attributes->getAttributes())->mapWithKeys(function ($value, $key) { return [Mini\Support\Str::camel($key) => $value]; })->all(), EXTR_SKIP); ?>
{{ props }}
<x-{{ component }} {{ bindings }} {{ attributes }}>
{{ slots }}
{{ defaultSlot }}
</x-{{ component }}>
EOF;

        return function ($data) use ($template) {
            $bindings = $this->bindings($class = $this->classForComponent());

            return str_replace(
                [
                    '{{ component }}',
                    '{{ props }}',
                    '{{ bindings }}',
                    '{{ attributes }}',
                    '{{ slots }}',
                    '{{ defaultSlot }}',
                ],
                [
                    $this->component,
                    $this->compileProps($bindings),
                    $this->compileBindings($bindings),
                    class_exists($class) ? '{{ $attributes }}' : '',
                    $this->compileSlots($data['__laravel_slots']),
                    '{{ $slot ?? "" }}',
                ],
                $template
            );
        };
    }

    /**
     * Compile the @props directive for the component.
     *
     * @param array $bindings
     * @return string
     */
    protected function compileProps(array $bindings): string
    {
        if (empty($bindings)) {
            return '';
        }

        return '@props(' . '[\'' . implode('\',\'', collect($bindings)->map(function ($dataKey) {
                return Str::camel($dataKey);
            })->all()) . '\']' . ')';
    }

    /**
     * Compile the bindings for the component.
     *
     * @param array $bindings
     * @return string
     */
    protected function compileBindings(array $bindings): string
    {
        return collect($bindings)->map(function ($key) {
            return ':' . $key . '="$' . Str::camel(str_replace([':', '.'], ' ', $key)) . '"';
        })->implode(' ');
    }

    /**
     * Compile the slots for the component.
     *
     * @param array $slots
     * @return string
     */
    protected function compileSlots(array $slots): string
    {
        return collect($slots)->map(function ($slot, $name) {
            return $name === '__default' ? null : '<x-slot name="' . $name . '" ' . ((string)$slot->attributes) . '>{{ $' . $name . ' }}</x-slot>';
        })->filter()->implode(PHP_EOL);
    }

    /**
     * Get the class for the current component.
     *
     * @return string
     * @throws BindingResolutionException
     */
    protected function classForComponent(): string
    {
        return static::$componentClasses[$this->component] ?? (static::$componentClasses[$this->component] =
                $this->compiler()->componentClass($this->component));
    }

    /**
     * Get the names of the variables that should be bound to the component.
     *
     * @param string $class
     * @return array
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    protected function bindings(string $class): array
    {
        [$data, $attributes] = $this->compiler()->partitionDataAndAttributes($class, $this->attributes->getAttributes());

        return array_keys($data->all());
    }

    /**
     * Get an instance of the Blade tag compiler.
     *
     * @return ComponentTagCompiler
     * @throws BindingResolutionException
     */
    protected function compiler(): ComponentTagCompiler
    {
        if (!static::$compiler) {
            static::$compiler = new ComponentTagCompiler(
                Container::getInstance()->make('blade.compiler')->getClassComponentAliases(),
                Container::getInstance()->make('blade.compiler')->getClassComponentNamespaces(),
                Container::getInstance()->make('blade.compiler')
            );
        }

        return static::$compiler;
    }
}
