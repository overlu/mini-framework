<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Countable;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;

interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param mixed|array $items
     * @return static
     */
    public static function make(mixed $items = []);

    /**
     * Create a new instance by invoking the callback a given amount of times.
     *
     * @param int $number
     * @param callable|null $callback
     * @return static
     */
    public static function times(int $number, callable $callback = null): static;

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @param mixed $value
     * @return static
     */
    public static function wrap(mixed $value): static;

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @param array|static $value
     * @return array
     */
    public static function unwrap(Enumerable|array $value): array;

    /**
     * Get all items in the enumerable.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Alias for the "avg" method.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function average(callable|string $callback = null): mixed;

    /**
     * Get the median of a given key.
     *
     * @param array|string|null $key
     * @return mixed
     */
    public function median(array|string $key = null): mixed;

    /**
     * Get the mode of a given key.
     *
     * @param array|string|null $key
     * @return array|null
     */
    public function mode(array|string $key = null): ?array;

    /**
     * Collapse the items into a single enumerable.
     *
     * @return self
     */
    public function collapse(): self;

    /**
     * Alias for the "contains" method.
     *
     * @param mixed $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return bool
     */
    public function some(mixed $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Determine if an item exists, using strict comparison.
     *
     * @param mixed $key
     * @param mixed|null $value
     * @return bool
     */
    public function containsStrict(mixed $key, mixed $value = null): bool;

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function avg(callable|string $callback = null): mixed;

    /**
     * Determine if an item exists in the enumerable.
     *
     * @param mixed $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return bool
     */
    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Dump the collection and end the script.
     *
     * @param mixed ...$args
     * @return void
     */
    public function dd(...$args): void;

    /**
     * Dump the collection.
     *
     * @return $this
     */
    public function dump(): self;

    /**
     * Get the items that are not present in the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function diff(mixed $items): static;

    /**
     * Get the items that are not present in the given items, using the callback.
     *
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffUsing(mixed $items, callable $callback): static;

    /**
     * Get the items whose keys and values are not present in the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function diffAssoc(mixed $items): static;

    /**
     * Get the items whose keys and values are not present in the given items, using the callback.
     *
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffAssocUsing(mixed $items, callable $callback): static;

    /**
     * Get the items whose keys are not present in the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function diffKeys(mixed $items): static;

    /**
     * Get the items whose keys are not present in the given items, using the callback.
     *
     * @param mixed $items
     * @param callable $callback
     * @return static
     */
    public function diffKeysUsing(mixed $items, callable $callback): static;

    /**
     * Retrieve duplicate items.
     *
     * @param callable|null $callback
     * @param bool $strict
     * @return static
     */
    public function duplicates(callable $callback = null, bool $strict = false): static;

    /**
     * Retrieve duplicate items using strict comparison.
     *
     * @param callable|null $callback
     * @return static
     */
    public function duplicatesStrict(callable $callback = null): static;

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback): self;

    /**
     * Execute a callback over each nested chunk of items.
     *
     * @param callable $callback
     * @return static
     */
    public function eachSpread(callable $callback): static;

    /**
     * Determine if all items pass the given truth test.
     *
     * @param callable|string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return bool
     */
    public function every(callable|string $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Get all items except for those with the specified keys.
     *
     * @param mixed $keys
     * @return static
     */
    public function except(mixed $keys): static;

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null): static;

    /**
     * Apply the callback if the value is truthy.
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function when(bool $value, callable $callback, callable $default = null): mixed;

    /**
     * Apply the callback if the collection is empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function whenEmpty(callable $callback, callable $default = null): mixed;

    /**
     * Apply the callback if the collection is not empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function whenNotEmpty(callable $callback, callable $default = null): mixed;

    /**
     * Apply the callback if the value is falsy.
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unless(bool $value, callable $callback, callable $default = null): mixed;

    /**
     * Apply the callback unless the collection is empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unlessEmpty(callable $callback, callable $default = null): mixed;

    /**
     * Apply the callback unless the collection is not empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unlessNotEmpty(callable $callback, callable $default = null): mixed;

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return static
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function whereStrict(string $key, mixed $value): static;

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereIn(string $key, mixed $values, bool $strict = false): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed $values
     * @return static
     */
    public function whereInStrict(string $key, mixed $values): static;

    /**
     * Filter items such that the value of the given key is between the given values.
     *
     * @param string $key
     * @param array $values
     * @return static
     */
    public function whereBetween(string $key, array $values): static;

    /**
     * Filter items such that the value of the given key is not between the given values.
     *
     * @param string $key
     * @param array $values
     * @return static
     */
    public function whereNotBetween(string $key, array $values): static;

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereNotIn(string $key, mixed $values, bool $strict = false): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed $values
     * @return static
     */
    public function whereNotInStrict(string $key, mixed $values): static;

    /**
     * Filter the items, removing any items that don't match the given type.
     *
     * @param string $type
     * @return static
     */
    public function whereInstanceOf(string $type): static;

    /**
     * Get the first item from the enumerable passing the given truth test.
     *
     * @param callable|null $callback
     * @param mixed|null $default
     * @return mixed
     */
    public function first(callable $callback = null, mixed $default = null): mixed;

    /**
     * Get the first item by the given key value pair.
     *
     * @param string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return mixed
     */
    public function firstWhere(string $key, mixed $operator = null, mixed $value = null): mixed;

    /**
     * Flip the values with their keys.
     *
     * @return self
     */
    public function flip(): self;

    /**
     * Get an item from the collection by key.
     *
     * @param mixed $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null): mixed;

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param callable|array|string $groupBy
     * @param bool $preserveKeys
     * @return static
     */
    public function groupBy(callable|array|string $groupBy, bool $preserveKeys = false): static;

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param callable|string $keyBy
     * @return static
     */
    public function keyBy(callable|string $keyBy): static;

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param mixed $key
     * @return bool
     */
    public function has(mixed $key): bool;

    /**
     * Concatenate values of a given key as a string.
     *
     * @param string $value
     * @param string|null $glue
     * @return string
     */
    public function implode(string $value, string $glue = null): string;

    /**
     * Intersect the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function intersect(mixed $items): static;

    /**
     * Intersect the collection with the given items by key.
     *
     * @param mixed $items
     * @return static
     */
    public function intersectByKeys(mixed $items): static;

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool;

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param string $glue
     * @param string $finalGlue
     * @return string
     */
    public function join(string $glue, string $finalGlue = ''): string;

    /**
     * Get the keys of the collection items.
     *
     * @return self
     */
    public function keys(): self;

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param mixed|null $default
     * @return mixed
     */
    public function last(callable $callback = null, mixed $default = null): mixed;

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback);

    /**
     * Run a map over each nested chunk of items.
     *
     * @param callable $callback
     * @return static
     */
    public function mapSpread(callable $callback): static;

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapToDictionary(callable $callback): static;

    /**
     * Run a grouping map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapToGroups(callable $callback): static;

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapWithKeys(callable $callback): static;

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable $callback
     * @return static
     */
    public function flatMap(callable $callback): static;

    /**
     * Map the values into a new class.
     *
     * @param string $class
     * @return static
     */
    public function mapInto(string $class): static;

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function merge(mixed $items): static;

    /**
     * Recursively merge the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function mergeRecursive(mixed $items): static;

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param mixed $values
     * @return static
     */
    public function combine(mixed $values): static;

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function union(mixed $items): static;

    /**
     * Get the min value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function min(callable|string $callback = null): mixed;

    /**
     * Get the max value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function max(callable|string $callback = null): mixed;

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     * @return static
     */
    public function nth(int $step, int $offset = 0): static;

    /**
     * Get the items with the specified keys.
     *
     * @param mixed $keys
     * @return static
     */
    public function only(mixed $keys): static;

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param int $page
     * @param int $perPage
     * @return static
     */
    public function forPage(int $page, int $perPage): static;

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param callable|string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return static
     */
    public function partition(callable|string $key, mixed $operator = null, mixed $value = null): static;

    /**
     * Push all of the given items onto the collection.
     *
     * @param iterable $source
     * @return static
     */
    public function concat(iterable $source): static;

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @param int|null $number
     * @return static|mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random(int $number = null): mixed;

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed|null $initial
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed;

    /**
     * Replace the collection items with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function replace(mixed $items): static;

    /**
     * Recursively replace the collection items with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function replaceRecursive(mixed $items): static;

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse(): static;

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool $strict
     * @return mixed
     */
    public function search(mixed $value, bool $strict = false): mixed;

    /**
     * Shuffle the items in the collection.
     *
     * @param int|null $seed
     * @return static
     */
    public function shuffle(int $seed = null): static;

    /**
     * Skip the first {$count} items.
     *
     * @param int $count
     * @return static
     */
    public function skip(int $count): static;

    /**
     * Get a slice of items from the enumerable.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, int $length = null): static;

    /**
     * Split a collection into a certain number of groups.
     *
     * @param int $numberOfGroups
     * @return static
     */
    public function split(int $numberOfGroups): static;

    /**
     * Chunk the collection into chunks of the given size.
     *
     * @param int $size
     * @return static
     */
    public function chunk(int $size): static;

    /**
     * Sort through each item with a callback.
     *
     * @param callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null): static;

    /**
     * Sort the collection using the given callback.
     *
     * @param callable|string $callback
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortBy(callable|string $callback, int $options = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param callable|string $callback
     * @param int $options
     * @return static
     */
    public function sortByDesc(callable|string $callback, int $options = SORT_REGULAR): static;

    /**
     * Sort the collection keys.
     *
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sort the collection keys in descending order.
     *
     * @param int $options
     * @return static
     */
    public function sortKeysDesc(int $options = SORT_REGULAR): static;

    /**
     * Get the sum of the given values.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function sum(callable|string $callback = null): mixed;

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     * @return static
     */
    public function take(int $limit): static;

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param callable $callback
     * @return $this
     */
    public function tap(callable $callback): self;

    /**
     * Pass the enumerable to the given callback and return the result.
     *
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback): mixed;

    /**
     * Get the values of a given key.
     *
     * @param array|string $value
     * @param string|null $key
     * @return static
     */
    public function pluck(mixed $value, string $key = null): self;

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param mixed|bool $callback
     * @return static
     */
    public function reject(mixed $callback = true): static;

    /**
     * Return only unique items from the collection array.
     *
     * @param callable|string|null $key
     * @param bool $strict
     * @return self
     */
    public function unique(callable|string $key = null, bool $strict = false): self;

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param callable|string|null $key
     * @return static
     */
    public function uniqueStrict(callable|string $key = null): static;

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values(): static;

    /**
     * Pad collection to the specified length with a value.
     *
     * @param int $size
     * @param mixed $value
     * @return self
     */
    public function pad(int $size, mixed $value): self;

    /**
     * Count the number of items in the collection using a given truth test.
     *
     * @param callable|null $callback
     * @return static
     */
    public function countBy(callable $callback = null): static;

    /**
     * Collect the values into a collection.
     *
     * @return Collection
     */
    public function collect(): Collection;

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString();

    /**
     * Add a method to the list of proxied methods.
     *
     * @param string $method
     * @return void
     */
    public static function proxy(string $method): void;

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get(string $key);
}
