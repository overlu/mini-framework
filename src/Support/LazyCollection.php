<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use ArrayIterator;
use Closure;
use Mini\Support\Traits\EnumeratesValues;
use Mini\Support\Traits\Macroable;
use IteratorAggregate;
use stdClass;
use Traversable;

class LazyCollection implements Enumerable
{
    use EnumeratesValues, Macroable;

    /**
     * The source from which to generate items.
     *
     * @var callable|static
     */
    public $source;

    /**
     * Create a new lazy collection instance.
     *
     * @param mixed|null $source
     * @return void
     */
    public function __construct(mixed $source = null)
    {
        if ($source instanceof Closure || $source instanceof self) {
            $this->source = $source;
        } elseif (is_null($source)) {
            $this->source = static::empty();
        } else {
            $this->source = $this->getArrayableItems($source);
        }
    }

    /**
     * Create a new instance with no items.
     *
     * @return static
     */
    public static function empty(): static
    {
        return new static([]);
    }

    /**
     * Create a new instance by invoking the callback a given amount of times.
     *
     * @param int $number
     * @param callable|null $callback
     * @return static
     */
    public static function times(int $number, callable $callback = null): static
    {
        if ($number < 1) {
            return new static;
        }

        $instance = new static(function () use ($number) {
            for ($current = 1; $current <= $number; $current++) {
                yield $current;
            }
        });

        return is_null($callback) ? $instance : $instance->map($callback);
    }

    /**
     * Create an enumerable with the given range.
     *
     * @param int $from
     * @param int $to
     * @return static
     */
    public static function range(int $from, int $to): static
    {
        return new static(function () use ($from, $to) {
            for (; $from <= $to; $from++) {
                yield $from;
            }
        });
    }

    /**
     * Get all items in the enumerable.
     *
     * @return array
     */
    public function all(): array
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        return iterator_to_array($this->getIterator());
    }

    /**
     * Eager load all items into a new lazy collection backed by an array.
     *
     * @return static
     */
    public function eager(): static
    {
        return new static($this->all());
    }

    /**
     * Cache values as they're enumerated.
     *
     * @return static
     */
    public function remember(): static
    {
        $iterator = $this->getIterator();

        $iteratorIndex = 0;

        $cache = [];

        return new static(function () use ($iterator, &$iteratorIndex, &$cache) {
            for ($index = 0; true; $index++) {
                if (array_key_exists($index, $cache)) {
                    yield $cache[$index][0] => $cache[$index][1];

                    continue;
                }

                if ($iteratorIndex < $index) {
                    $iterator->next();

                    $iteratorIndex++;
                }

                if (!$iterator->valid()) {
                    break;
                }

                $cache[$index] = [$iterator->key(), $iterator->current()];

                yield $cache[$index][0] => $cache[$index][1];
            }
        });
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function avg(callable|string $callback = null): mixed
    {
        return $this->collect()->avg($callback);
    }

    /**
     * Get the median of a given key.
     *
     * @param array|string|null $key
     * @return mixed
     */
    public function median(array|string $key = null): mixed
    {
        return $this->collect()->median($key);
    }

    /**
     * Get the mode of a given key.
     *
     * @param array|string|null $key
     * @return array|null
     */
    public function mode(array|string $key = null): ?array
    {
        return $this->collect()->mode($key);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse(): static
    {
        return new static(function () {
            foreach ($this as $values) {
                if (is_array($values) || $values instanceof Enumerable) {
                    foreach ($values as $value) {
                        yield $value;
                    }
                }
            }
        });
    }

    /**
     * Determine if an item exists in the enumerable.
     *
     * @param mixed $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return bool
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1 && $this->useAsCallable($key)) {
            $placeholder = new stdClass;

            return $this->first($key, $placeholder) !== $placeholder;
        }

        if (func_num_args() === 1) {
            $needle = $key;

            foreach ($this as $val) {
                if ($val === $needle) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Cross join the given iterables, returning all possible permutations.
     *
     * @param array ...$arrays
     * @return static
     */
    public function crossJoin(...$arrays): static
    {
        return $this->passthru('crossJoin', func_get_args());
    }

    /**
     * Get the items that are not present in the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function diff(mixed $items): static
    {
        return $this->passthru('diff', func_get_args());
    }

    /**
     * Get the items that are not present in the given items, using the callback.
     *
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffUsing(mixed $items, callable $callback): static
    {
        return $this->passthru('diffUsing', func_get_args());
    }

    /**
     * Get the items whose keys and values are not present in the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function diffAssoc(mixed $items): static
    {
        return $this->passthru('diffAssoc', func_get_args());
    }

    /**
     * Get the items whose keys and values are not present in the given items, using the callback.
     *
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffAssocUsing(mixed $items, callable $callback): static
    {
        return $this->passthru('diffAssocUsing', func_get_args());
    }

    /**
     * Get the items whose keys are not present in the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function diffKeys(mixed $items): static
    {
        return $this->passthru('diffKeys', func_get_args());
    }

    /**
     * Get the items whose keys are not present in the given items, using the callback.
     *
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffKeysUsing(mixed $items, callable $callback): static
    {
        return $this->passthru('diffKeysUsing', func_get_args());
    }

    /**
     * Retrieve duplicate items.
     *
     * @param callable|null $callback
     * @param bool $strict
     * @return static
     */
    public function duplicates(callable $callback = null, bool $strict = false): static
    {
        return $this->passthru('duplicates', func_get_args());
    }

    /**
     * Retrieve duplicate items using strict comparison.
     *
     * @param callable|null $callback
     * @return static
     */
    public function duplicatesStrict(callable $callback = null): static
    {
        return $this->passthru('duplicatesStrict', func_get_args());
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param mixed $keys
     * @return static
     */
    public function except(mixed $keys): static
    {
        return $this->passthru('except', func_get_args());
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null): static
    {
        if (is_null($callback)) {
            $callback = function ($value) {
                return (bool)$value;
            };
        }

        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * Get the first item from the enumerable passing the given truth test.
     *
     * @param callable|null $callback
     * @param mixed|null $default
     * @return mixed
     */
    public function first(callable $callback = null, mixed $default = null): mixed
    {
        $iterator = $this->getIterator();

        if (is_null($callback)) {
            if (!$iterator->valid()) {
                return value($default);
            }

            return $iterator->current();
        }

        foreach ($iterator as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Get a flattened list of the items in the collection.
     *
     * @param int $depth
     * @return static
     */
    public function flatten(int $depth): static
    {
        $instance = new static(function () use ($depth) {
            foreach ($this as $item) {
                if (!is_array($item) && !$item instanceof Enumerable) {
                    yield $item;
                } elseif ($depth === 1) {
                    yield from $item;
                } else {
                    yield from (new static($item))->flatten($depth - 1);
                }
            }
        });

        return $instance->values();
    }

    /**
     * Flip the items in the collection.
     *
     * @return static
     */
    public function flip(): static
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $value => $key;
            }
        });
    }

    /**
     * Get an item by key.
     *
     * @param mixed $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $default;
        }

        foreach ($this as $outerKey => $outerValue) {
            if ($outerKey === $key) {
                return $outerValue;
            }
        }

        return value($default);
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param callable|array|string $groupBy
     * @param bool $preserveKeys
     * @return static
     */
    public function groupBy(callable|array|string $groupBy, bool $preserveKeys = false): static
    {
        return $this->passthru('groupBy', func_get_args());
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param callable|string $keyBy
     * @return static
     */
    public function keyBy(callable|string $keyBy): static
    {
        return new static(function () use ($keyBy) {
            $keyBy = $this->valueRetriever($keyBy);

            foreach ($this as $key => $item) {
                $resolvedKey = $keyBy($item, $key);

                if (is_object($resolvedKey)) {
                    $resolvedKey = (string)$resolvedKey;
                }

                yield $resolvedKey => $item;
            }
        });
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param mixed $key
     * @return bool
     */
    public function has(mixed $key): bool
    {
        $keys = array_flip(is_array($key) ? $key : func_get_args());
        $count = count($keys);

        foreach ($this as $k => $value) {
            if (array_key_exists($k, $keys) && --$count === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param string $value
     * @param string|null $glue
     * @return string
     */
    public function implode(string $value, string $glue = null): string
    {
        return $this->collect()->implode(...func_get_args());
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function intersect(mixed $items): static
    {
        return $this->passthru('intersect', func_get_args());
    }

    /**
     * Intersect the collection with the given items by key.
     *
     * @param mixed $items
     * @return static
     */
    public function intersectByKeys(mixed $items): static
    {
        return $this->passthru('intersectByKeys', func_get_args());
    }

    /**
     * Determine if the items is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->getIterator()->valid();
    }

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param string $glue
     * @param string $finalGlue
     * @return string
     */
    public function join(string $glue, string $finalGlue = ''): string
    {
        return $this->collect()->join(...func_get_args());
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys(): static
    {
        return new static(function () {
            foreach ($this as $key => $value) {
                yield $key;
            }
        });
    }

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param mixed|null $default
     * @return mixed
     */
    public function last(callable $callback = null, mixed $default = null): mixed
    {
        $needle = $placeholder = new stdClass;

        foreach ($this as $key => $value) {
            if (is_null($callback) || $callback($value, $key)) {
                $needle = $value;
            }
        }

        return $needle === $placeholder ? value($default) : $needle;
    }

    /**
     * Get the values of a given key.
     *
     * @param array|string $value
     * @param string|null $key
     * @return static
     */
    public function pluck(mixed $value, string $key = null): static
    {
        return new static(function () use ($value, $key) {
            [$value, $key] = $this->explodePluckParameters($value, $key);

            foreach ($this as $item) {
                $itemValue = data_get($item, $value);

                if (is_null($key)) {
                    yield $itemValue;
                } else {
                    $itemKey = data_get($item, $key);

                    if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                        $itemKey = (string)$itemKey;
                    }

                    yield $itemKey => $itemValue;
                }
            }
        });
    }

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapToDictionary(callable $callback): static
    {
        return $this->passthru('mapToDictionary', func_get_args());
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapWithKeys(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                yield from $callback($value, $key);
            }
        });
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function merge(mixed $items): static
    {
        return $this->passthru('merge', func_get_args());
    }

    /**
     * Recursively merge the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function mergeRecursive(mixed $items): static
    {
        return $this->passthru('mergeRecursive', func_get_args());
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param mixed $values
     * @return static
     */
    public function combine(mixed $values): static
    {
        return new static(function () use ($values) {
            $values = $this->makeIterator($values);

            $errorMessage = 'Both parameters should have an equal number of elements';

            foreach ($this as $key) {
                if (!$values->valid()) {
                    trigger_error($errorMessage, E_USER_WARNING);

                    break;
                }

                yield $key => $values->current();

                $values->next();
            }

            if ($values->valid()) {
                trigger_error($errorMessage, E_USER_WARNING);
            }
        });
    }

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function union(mixed $items): static
    {
        return $this->passthru('union', func_get_args());
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     * @return static
     */
    public function nth(int $step, int $offset = 0): static
    {
        return new static(function () use ($step, $offset) {
            $position = 0;

            foreach ($this as $item) {
                if ($position % $step === $offset) {
                    yield $item;
                }

                $position++;
            }
        });
    }

    /**
     * Get the items with the specified keys.
     *
     * @param mixed $keys
     * @return static
     */
    public function only(mixed $keys): static
    {
        if ($keys instanceof Enumerable) {
            $keys = $keys->all();
        } elseif (!is_null($keys)) {
            $keys = is_array($keys) ? $keys : func_get_args();
        }

        return new static(function () use ($keys) {
            if (is_null($keys)) {
                yield from $this;
            } else {
                $keys = array_flip($keys);

                foreach ($this as $key => $value) {
                    if (array_key_exists($key, $keys)) {
                        yield $key => $value;

                        unset($keys[$key]);

                        if (empty($keys)) {
                            break;
                        }
                    }
                }
            }
        });
    }

    /**
     * Push all of the given items onto the collection.
     *
     * @param iterable $source
     * @return static
     */
    public function concat(iterable $source): static
    {
        return (new static(function () use ($source) {
            yield from $this;
            yield from $source;
        }))->values();
    }

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @param int|null $number
     * @return static|mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random(int $number = null): mixed
    {
        $result = $this->collect()->random(...func_get_args());

        return is_null($number) ? $result : new static($result);
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;

        foreach ($this as $value) {
            $result = $callback($result, $value);
        }

        return $result;
    }

    /**
     * Replace the collection items with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function replace(mixed $items): static
    {
        return new static(function () use ($items) {
            $items = $this->getArrayableItems($items);

            foreach ($this as $key => $value) {
                if (array_key_exists($key, $items)) {
                    yield $key => $items[$key];

                    unset($items[$key]);
                } else {
                    yield $key => $value;
                }
            }

            foreach ($items as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * Recursively replace the collection items with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function replaceRecursive(mixed $items): static
    {
        return $this->passthru('replaceRecursive', func_get_args());
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse(): static
    {
        return $this->passthru('reverse', func_get_args());
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool $strict
     * @return string|int|bool|null
     */
    public function search(mixed $value, bool $strict = false): string|int|bool|null
    {
        $predicate = $this->useAsCallable($value)
            ? $value
            : function ($item) use ($value, $strict) {
                return $strict ? $item === $value : $item == $value;
            };

        foreach ($this as $key => $item) {
            if ($predicate($item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Shuffle the items in the collection.
     *
     * @param int|null $seed
     * @return static
     */
    public function shuffle(int $seed = null): static
    {
        return $this->passthru('shuffle', func_get_args());
    }

    /**
     * Skip the first {$count} items.
     *
     * @param int $count
     * @return static
     */
    public function skip(int $count): static
    {
        return new static(function () use ($count) {
            $iterator = $this->getIterator();

            while ($iterator->valid() && $count--) {
                $iterator->next();
            }

            while ($iterator->valid()) {
                yield $iterator->key() => $iterator->current();

                $iterator->next();
            }
        });
    }

    /**
     * Get a slice of items from the enumerable.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, int $length = null): static
    {
        if ($offset < 0 || $length < 0) {
            return $this->passthru('slice', func_get_args());
        }

        $instance = $this->skip($offset);

        return is_null($length) ? $instance : $instance->take($length);
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * @param int $numberOfGroups
     * @return static
     */
    public function split(int $numberOfGroups): static
    {
        return $this->passthru('split', func_get_args());
    }

    /**
     * Chunk the collection into chunks of the given size.
     *
     * @param int $size
     * @return static
     */
    public function chunk(int $size): static
    {
        if ($size <= 0) {
            return static::empty();
        }

        return new static(function () use ($size) {
            $iterator = $this->getIterator();

            while ($iterator->valid()) {
                $chunk = [];

                while (true) {
                    $chunk[$iterator->key()] = $iterator->current();

                    if (count($chunk) < $size) {
                        $iterator->next();

                        if (!$iterator->valid()) {
                            break;
                        }
                    } else {
                        break;
                    }
                }

                yield new static($chunk);

                $iterator->next();
            }
        });
    }

    /**
     * Sort through each item with a callback.
     *
     * @param callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null): static
    {
        return $this->passthru('sort', func_get_args());
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param callable|string $callback
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortBy(callable|string $callback, int $options = SORT_REGULAR, bool $descending = false): static
    {
        return $this->passthru('sortBy', func_get_args());
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param callable|string $callback
     * @param int $options
     * @return static
     */
    public function sortByDesc(callable|string $callback, int $options = SORT_REGULAR): static
    {
        return $this->passthru('sortByDesc', func_get_args());
    }

    /**
     * Sort the collection keys.
     *
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static
    {
        return $this->passthru('sortKeys', func_get_args());
    }

    /**
     * Sort the collection keys in descending order.
     *
     * @param int $options
     * @return static
     */
    public function sortKeysDesc(int $options = SORT_REGULAR): static
    {
        return $this->passthru('sortKeysDesc', func_get_args());
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     * @return static
     */
    public function take(int $limit): static
    {
        if ($limit < 0) {
            return $this->passthru('take', func_get_args());
        }

        return new static(function () use ($limit) {
            $iterator = $this->getIterator();

            while ($limit--) {
                if (!$iterator->valid()) {
                    break;
                }

                yield $iterator->key() => $iterator->current();

                if ($limit) {
                    $iterator->next();
                }
            }
        });
    }

    /**
     * Pass each item in the collection to the given callback, lazily.
     *
     * @param callable $callback
     * @return static
     */
    public function tapEach(callable $callback): static
    {
        return new static(function () use ($callback) {
            foreach ($this as $key => $value) {
                $callback($value, $key);

                yield $key => $value;
            }
        });
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values(): static
    {
        return new static(function () {
            foreach ($this as $item) {
                yield $item;
            }
        });
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new LazyCollection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param mixed ...$items
     * @return static
     */
    public function zip(mixed $items): static
    {
        $iterables = func_get_args();

        return new static(function () use ($iterables) {
            $iterators = Collection::make($iterables)->map(function ($iterable) {
                return $this->makeIterator($iterable);
            })->prepend($this->getIterator());

            while ($iterators->contains->valid()) {
                yield new static($iterators->map->current());

                $iterators->each->next();
            }
        });
    }

    /**
     * Pad collection to the specified length with a value.
     *
     * @param int $size
     * @param mixed $value
     * @return static
     */
    public function pad(int $size, mixed $value): static
    {
        if ($size < 0) {
            return $this->passthru('pad', func_get_args());
        }

        return new static(function () use ($size, $value) {
            $yielded = 0;

            foreach ($this as $index => $item) {
                yield $index => $item;

                $yielded++;
            }

            while ($yielded++ < $size) {
                yield $value;
            }
        });
    }

    /**
     * Get the values iterator.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->makeIterator($this->source);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        if (is_array($this->source)) {
            return count($this->source);
        }

        return iterator_count($this->getIterator());
    }

    /**
     * Make an iterator from the given source.
     *
     * @param mixed $source
     * @return Traversable
     */
    protected function makeIterator(mixed $source): Traversable
    {
        if ($source instanceof IteratorAggregate) {
            return $source->getIterator();
        }

        if (is_array($source)) {
            return new ArrayIterator($source);
        }

        return $source();
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param array|string $value
     * @param array|string|null $key
     * @return array
     */
    protected function explodePluckParameters(array|string $value, array|string|null $key): array
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Pass this lazy collection through a method on the collection class.
     *
     * @param string $method
     * @param array $params
     * @return static
     */
    protected function passthru(string $method, array $params): static
    {
        return new static(function () use ($method, $params) {
            yield from $this->collect()->$method(...$params);
        });
    }
}
