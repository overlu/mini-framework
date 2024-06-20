<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use ArrayAccess;
use ArrayIterator;
use Mini\Contracts\Support\Htmlable;
use Mini\Support\Arr;
use Mini\Support\HtmlString;
use Mini\Support\Str;
use Mini\Support\Traits\Conditionable;
use Mini\Support\Traits\Macroable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class ComponentAttributeBag implements ArrayAccess, IteratorAggregate, JsonSerializable, Htmlable
{
    use Conditionable, Macroable;

    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Create a new component attribute bag instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the first attribute's value.
     *
     * @param mixed|null $default
     * @return mixed
     */
    public function first(mixed $default = null): mixed
    {
        return $this->getIterator()->current() ?? value($default);
    }

    /**
     * Get a given attribute from the attribute array.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? value($default);
    }

    /**
     * Determine if a given attribute exists in the attribute array.
     *
     * @param array|string $key
     * @return bool
     */
    public function has(array|string $key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (!array_key_exists($value, $this->attributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in the attribute array.
     *
     * @param array|string $key
     * @return bool
     */
    public function hasAny(array|string $key): bool
    {
        if (!count($this->attributes)) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->has($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given attribute is missing from the attribute array.
     *
     * @param string|array $key
     * @return bool
     */
    public function missing(string|array $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Only include the given attribute from the attribute array.
     *
     * @param mixed|array $keys
     * @return static
     */
    public function only(mixed $keys): static
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::only($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param mixed|array $keys
     * @return static
     */
    public function except(mixed $keys): static
    {
        if (is_null($keys)) {
            $values = $this->attributes;
        } else {
            $keys = Arr::wrap($keys);

            $values = Arr::except($this->attributes, $keys);
        }

        return new static($values);
    }

    /**
     * Filter the attributes, returning a bag of attributes that pass the filter.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        return new static(collect($this->attributes)->filter($callback)->all());
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param string $string
     * @return static
     */
    public function whereStartsWith(string $string): static
    {
        return $this->filter(static function ($value, $key) use ($string) {
            return Str::startsWith($key, $string);
        });
    }

    /**
     * Return a bag of attributes with keys that do not start with the given value / pattern.
     *
     * @param string|string[] $needles
     * @return static
     */
    public function whereDoesntStartWith(array|string $needles): static
    {
        return $this->filter(function ($value, $key) use ($needles) {
            return !Str::startsWith($key, $needles);
        });
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param string $string
     * @return static
     */
    public function thatStartWith(string $string): static
    {
        return $this->whereStartsWith($string);
    }

    /**
     * Only include the given attribute from the attribute array.
     *
     * @param mixed|array $keys
     * @return static
     */
    public function onlyProps(mixed $keys): static
    {
        return $this->only($this->extractPropNames($keys));
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param mixed|array $keys
     * @return static
     */
    public function exceptProps(mixed $keys): static
    {
        return $this->except($this->extractPropNames($keys));
    }

    /**
     * Extract prop names from given keys.
     *
     * @param mixed|array $keys
     * @return array
     */
    protected function extractPropNames(mixed $keys): array
    {
        $props = [];

        foreach ($keys as $key => $defaultValue) {
            $key = is_numeric($key) ? $defaultValue : $key;

            $props[] = $key;
            $props[] = Str::kebab($key);
        }

        return $props;
    }

    /**
     * Conditionally merge classes into the attribute bag.
     *
     * @param mixed|array $classList
     * @return static
     */
    public function class(mixed $classList): static
    {
        $classList = Arr::wrap($classList);

        return $this->merge(['class' => Arr::toCssClasses($classList)]);
    }

    /**
     * Conditionally merge styles into the attribute bag.
     *
     * @param mixed|array $styleList
     * @return static
     */
    public function style(mixed $styleList): static
    {
        $styleList = Arr::wrap($styleList);

        return $this->merge(['style' => Arr::toCssStyles($styleList)]);
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param array $attributeDefaults
     * @param bool $escape
     * @return static
     */
    public function merge(array $attributeDefaults = [], bool $escape = true): static
    {
        $attributeDefaults = array_map(function ($value) use ($escape) {
            return $this->shouldEscapeAttributeValue($escape, $value)
                ? e($value)
                : $value;
        }, $attributeDefaults);

        [$appendableAttributes, $nonAppendableAttributes] = collect($this->attributes)
            ->partition(function ($value, $key) use ($attributeDefaults) {
                return $key === 'class' || $key === 'style' || (
                        isset($attributeDefaults[$key]) &&
                        $attributeDefaults[$key] instanceof AppendableAttributeValue
                    );
            });

        $attributes = $appendableAttributes->mapWithKeys(function ($value, $key) use ($attributeDefaults, $escape) {
            $defaultsValue = isset($attributeDefaults[$key]) && $attributeDefaults[$key] instanceof AppendableAttributeValue
                ? $this->resolveAppendableAttributeDefault($attributeDefaults, $key, $escape)
                : ($attributeDefaults[$key] ?? '');

            if ($key === 'style') {
                $value = Str::finish($value, ';');
            }

            return [$key => implode(' ', array_unique(array_filter([$defaultsValue, $value])))];
        })->merge($nonAppendableAttributes)->all();

        return new static(array_merge($attributeDefaults, $attributes));
    }

    /**
     * Determine if the specific attribute value should be escaped.
     *
     * @param bool $escape
     * @param mixed $value
     * @return bool
     */
    protected function shouldEscapeAttributeValue(bool $escape, mixed $value): bool
    {
        if (!$escape) {
            return false;
        }

        return !is_object($value) &&
            !is_null($value) &&
            !is_bool($value);
    }

    /**
     * Create a new appendable attribute value.
     *
     * @param mixed $value
     * @return AppendableAttributeValue
     */
    public function prepends(mixed $value): AppendableAttributeValue
    {
        return new AppendableAttributeValue($value);
    }

    /**
     * Resolve an appendable attribute value default value.
     *
     * @param array $attributeDefaults
     * @param string $key
     * @param bool $escape
     * @return mixed
     */
    protected function resolveAppendableAttributeDefault(array $attributeDefaults, string $key, bool $escape): mixed
    {
        if ($this->shouldEscapeAttributeValue($escape, $value = $attributeDefaults[$key]->value)) {
            $value = e($value);
        }

        return $value;
    }

    /**
     * Determine if the attribute bag is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return trim((string)$this) === '';
    }

    /**
     * Determine if the attribute bag is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get all of the raw attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set the underlying attributes.
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        if (isset($attributes['attributes']) &&
            $attributes['attributes'] instanceof self) {
            $parentBag = $attributes['attributes'];

            unset($attributes['attributes']);

            $attributes = $parentBag->merge($attributes, $escape = false)->getAttributes();
        }

        $this->attributes = $attributes;
    }

    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml(): string
    {
        return (string)$this;
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param array $attributeDefaults
     * @return HtmlString
     */
    public function __invoke(array $attributeDefaults = []): HtmlString
    {
        return new HtmlString((string)$this->merge($attributeDefaults));
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Remove the value at the given offset.
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->attributes);
    }

    /**
     * Convert the object into a JSON serializable form.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = '';

        foreach ($this->attributes as $key => $value) {
            if ($value === false || is_null($value)) {
                continue;
            }

            if ($value === true) {
                // Exception for Alpine...
                $value = $key === 'x-data' ? '' : $key;
            }

            $string .= ' ' . $key . '="' . str_replace('"', '\\"', trim($value)) . '"';
        }

        return trim($string);
    }
}
