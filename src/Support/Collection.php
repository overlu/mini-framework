<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use Closure;
use Countable;
use Exception;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use RuntimeException;
use stdClass;
use Symfony\Component\VarDumper\VarDumper;
use Traversable;

/**
 * Most of the methods in this file come from illuminate/support,
 * thanks Laravel Team provide such a useful class.
 *
 * @property HigherOrderCollectionProxy $average
 * @property HigherOrderCollectionProxy $avg
 * @property HigherOrderCollectionProxy $contains
 * @property HigherOrderCollectionProxy $each
 * @property HigherOrderCollectionProxy $every
 * @property HigherOrderCollectionProxy $filter
 * @property HigherOrderCollectionProxy $first
 * @property HigherOrderCollectionProxy $flatMap
 * @property HigherOrderCollectionProxy $groupBy
 * @property HigherOrderCollectionProxy $keyBy
 * @property HigherOrderCollectionProxy $map
 * @property HigherOrderCollectionProxy $max
 * @property HigherOrderCollectionProxy $min
 * @property HigherOrderCollectionProxy $partition
 * @property HigherOrderCollectionProxy $reject
 * @property HigherOrderCollectionProxy $sortBy
 * @property HigherOrderCollectionProxy $sortByDesc
 * @property HigherOrderCollectionProxy $sum
 * @property HigherOrderCollectionProxy $unique
 */
class Collection implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * The methods that can be proxied.
     *
     * @var array
     */
    protected static array $proxies = [
        'average',
        'avg',
        'contains',
        'each',
        'every',
        'filter',
        'first',
        'flatMap',
        'groupBy',
        'keyBy',
        'map',
        'max',
        'min',
        'partition',
        'reject',
        'sortBy',
        'sortByDesc',
        'sum',
        'unique',
    ];

    /**
     * Create a new collection.
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Convert the collection to its string representation.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     * @return HigherOrderCollectionProxy
     * @throws Exception
     */
    public function __get(string $key)
    {
        if (!in_array($key, static::$proxies, true)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }
        return new HigherOrderCollectionProxy($this, $key);
    }

    /**
     * Create a new collection instance if the value isn't one already.
     * @param mixed $items
     * @return Collection
     */
    public static function make($items = []): self
    {
        return new static($items);
    }

    /**
     * Wrap the given value in a collection if applicable.
     * @param mixed $value
     * @return Collection
     */
    public static function wrap($value): self
    {
        return $value instanceof self ? new static($value) : new static(Arr::wrap($value));
    }

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @param array|static $value
     * @return array
     */
    public static function unwrap($value): array
    {
        return $value instanceof self ? $value->all() : $value;
    }

    /**
     * Create a new collection by invoking the callback a given amount of times.
     * @param int $number
     * @param callable|null $callback
     * @return Collection
     */
    public static function times(int $number, callable $callback = null): self
    {
        if ($number < 1) {
            return new static();
        }
        if (is_null($callback)) {
            return new static(range(1, $number));
        }
        return (new static(range(1, $number)))->map($callback);
    }

    /**
     * Get all of the items in the collection.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the average value of a given key
     * @param null $callback
     * @return float|int|null
     */
    public function avg($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        $items = $this->map(static function ($value) use ($callback) {
            return $callback($value);
        })->filter(static function ($value) {
            return !is_null($value);
        });
        if ($count = $items->count()) {
            return $items->sum() / $count;
        }
        return null;
    }

    /**
     * Alias for the "avg" method.
     *
     * @param null|callable|string $callback
     * @return float|int
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * Get the median of a given key.
     *
     * @param null|mixed $key
     * @return float|int|mixed|void
     */
    public function median($key = null)
    {
        $values = (isset($key) ? $this->pluck($key) : $this)->filter(function ($item) {
            return !is_null($item);
        })->sort()->values();
        $count = $values->count();
        if ($count === 0) {
            return;
        }
        $middle = (int)($count / 2);
        if ($count % 2) {
            return $values->get($middle);
        }
        return (new static([
            $values->get($middle - 1),
            $values->get($middle),
        ]))->average();
    }

    /**
     * Get the mode of a given key.
     *
     * @param null|mixed $key
     * @return null|array|void
     */
    public function mode($key = null)
    {
        if ($this->count() === 0) {
            return;
        }
        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = new self();
        $collection->each(static function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        $sorted = $counts->sort();
        $highestValue = $sorted->last();
        return $sorted->filter(static function ($value) use ($highestValue) {
            return $value == $highestValue;
        })->sort()->keys()->all();
    }

    /**
     * Collapse the collection of items into a single array.
     */
    public function collapse(): self
    {
        return new static(Arr::collapse($this->items));
    }

    /**
     * Determine if an item exists in the collection.
     * @param null|mixed $operator
     * @param null|mixed $value
     * @param mixed $key
     * @return bool
     */
    public function contains($key, $operator = null, $value = null): bool
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                $placeholder = new stdClass();
                return $this->first($key, $placeholder) !== $placeholder;
            }
            return in_array($key, $this->items, true);
        }
        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Determine if an item exists in the collection using strict comparison.
     * @param null|mixed $value
     * @param mixed $key
     * @return bool
     */
    public function containsStrict($key, $value = null): bool
    {
        if (func_num_args() === 2) {
            return $this->contains(static function ($item) use ($key, $value) {
                return data_get($item, $key) === $value;
            });
        }
        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }
        return in_array($key, $this->items, true);
    }

    /**
     * Cross join with the given lists, returning all possible permutations.
     * @param array $lists
     * @return Collection
     */
    public function crossJoin(...$lists): self
    {
        return new static(Arr::crossJoin($this->items, ...array_map([$this, 'getArrayableItems'], $lists)));
    }

    /**
     * Dump the collection and end the script.
     * @param array $args
     */
    public function dd(...$args): void
    {
        call_user_func_array([$this, 'dump'], $args);
        die(1);
    }

    /**
     * Dump the collection.
     */
    public function dump(): self
    {
        (new static(func_get_args()))->push($this)->each(function ($item) {
            if (!class_exists(VarDumper::class)) {
                throw new RuntimeException('symfony/var-dumper package required, please require the package via "composer require symfony/var-dumper"');
            }
            VarDumper::dump($item);
        });
        return $this;
    }

    /**
     * Get the items in the collection that are not present in the given items.
     * @param mixed $items
     * @return Collection
     */
    public function diff($items): self
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the items in the collection that are not present in the given items.
     * @param mixed $items
     * @param callable $callback
     * @return Collection
     */
    public function diffUsing($items, callable $callback): self
    {
        return new static(array_udiff($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     * @param mixed $items
     * @return Collection
     */
    public function diffAssoc($items): self
    {
        return new static(array_diff_assoc($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     * @param mixed $items
     * @param callable $callback
     * @return Collection
     */
    public function diffAssocUsing($items, callable $callback): self
    {
        return new static(array_diff_uassoc($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     * @param mixed $items
     * @return Collection
     */
    public function diffKeys($items): self
    {
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     * @param mixed $items
     * @param callable $callback
     * @return Collection
     */
    public function diffKeysUsing($items, callable $callback): self
    {
        return new static(array_diff_ukey($this->items, $this->getArrayableItems($items), $callback));
    }

    /**
     * Execute a callback over each item.
     * @param callable $callback
     * @return Collection
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }
        return $this;
    }

    /**
     * Execute a callback over each nested chunk of items.
     * @param callable $callback
     * @return Collection
     */
    public function eachSpread(callable $callback): self
    {
        return $this->each(static function ($chunk, $key) use ($callback) {
            $chunk[] = $key;
            return $callback(...$chunk);
        });
    }

    /**
     * Determine if all items in the collection pass the given test.
     *
     * @param callable|string $key
     * @param null|mixed $operator
     * @param null|mixed $value
     * @return bool
     */
    public function every($key, $operator = null, $value = null): bool
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);
            foreach ($this->items as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }
            return true;
        }
        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param Collection|mixed $keys
     * @return Collection
     */
    public function except($keys): self
    {
        if ($keys instanceof self) {
            $keys = $keys->all();
        } elseif (!is_array($keys)) {
            $keys = func_get_args();
        }
        return new static(Arr::except($this->items, $keys));
    }

    /**
     * Run a filter over each of the items.
     * @param callable|null $callback
     * @return Collection
     */
    public function filter(callable $callback = null): self
    {
        if ($callback) {
            return new static(Arr::where($this->items, $callback));
        }
        return new static(array_filter($this->items));
    }

    /**
     * Apply the callback if the value is truthy.
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return Collection
     */
    public function when(bool $value, callable $callback, callable $default = null): self
    {
        if ($value) {
            return $callback($this, $value);
        }
        if ($default) {
            return $default($this, $value);
        }
        return $this;
    }

    /**
     * Apply the callback if the value is falsy.
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return Collection
     */
    public function unless(bool $value, callable $callback, callable $default = null): self
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * Filter items by the given key value pair.
     * @param string $key
     * @param null|mixed $operator
     * @param null|mixed $value
     * @return Collection
     */
    public function where(string $key, $operator = null, $value = null): self
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     * @param string $key
     * @param mixed $value
     * @return Collection
     */
    public function whereStrict(string $key, $value): self
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filter items by the given key value pair.
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return Collection
     */
    public function whereIn(string $key, $values, bool $strict = false): self
    {
        $values = $this->getArrayableItems($values);
        return $this->filter(static function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     * @param string $key
     * @param mixed $values
     * @return Collection
     */
    public function whereInStrict(string $key, $values): self
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items by the given key value pair.
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return Collection
     */
    public function whereNotIn(string $key, $values, bool $strict = false): self
    {
        $values = $this->getArrayableItems($values);
        return $this->reject(static function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     * @param string $key
     * @param mixed $values
     * @return Collection
     */
    public function whereNotInStrict(string $key, $values): self
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filter the items, removing any items that don't match the given type.
     * @param string $type
     * @return Collection
     */
    public function whereInstanceOf(string $type): self
    {
        return $this->filter(static function ($value) use ($type) {
            return $value instanceof $type;
        });
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param null|mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Get the first item by the given key value pair.
     *
     * @param string $key
     * @param mixed $operator
     * @param null|mixed $value
     * @return mixed
     */
    public function firstWhere(string $key, $operator, $value = null)
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get a flattened array of the items in the collection.
     * @param float|int $depth
     * @return Collection
     */
    public function flatten($depth = INF): self
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    /**
     * Flip the items in the collection.
     */
    public function flip(): self
    {
        return new static(array_flip($this->items));
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param array|string $keys
     * @return Collection
     */
    public function forget($keys): self
    {
        foreach ((array)$keys as $key) {
            $this->offsetUnset($key);
        }
        return $this;
    }

    /**
     * Get an item from the collection by key.
     * @param null|mixed $default
     * @param mixed $key
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }
        return value($default);
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param callable|string $groupBy
     * @param bool $preserveKeys
     * @return Collection
     */
    public function groupBy($groupBy, bool $preserveKeys = false): self
    {
        if (is_array($groupBy)) {
            $nextGroups = $groupBy;
            $groupBy = array_shift($nextGroups);
        }
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int)$groupKey : $groupKey;
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new static();
                }
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        $result = new static($results);
        if (!empty($nextGroups)) {
            return $result->map->groupBy($nextGroups, $preserveKeys);
        }
        return $result;
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param callable|string $keyBy
     * @return Collection
     */
    public function keyBy($keyBy): self
    {
        $keyBy = $this->valueRetriever($keyBy);
        $results = [];
        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);
            if (is_object($resolvedKey)) {
                $resolvedKey = (string)$resolvedKey;
            }
            $results[$resolvedKey] = $item;
        }
        return new static($results);
    }

    /**
     * Determine if an item exists in the collection by key.
     * @param mixed $key
     * @return bool
     */
    public function has($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if (!$this->offsetExists($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Concatenate values of a given key as a string.
     * @param string $value
     * @param string|null $glue
     * @return string
     */
    public function implode(string $value, string $glue = null): string
    {
        $first = $this->first();
        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }
        return implode($value, $this->items);
    }

    /**
     * Intersect the collection with the given items.
     * @param mixed $items
     * @return Collection
     */
    public function intersect($items): self
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Intersect the collection with the given items by key.
     * @param mixed $items
     * @return Collection
     */
    public function intersectByKeys($items): self
    {
        return new static(array_intersect_key($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Determine if the collection is empty or not.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the keys of the collection items.
     */
    public function keys(): self
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param null|mixed $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Get the values of a given key.
     *
     * @param array|string $value
     * @param string|null $key
     * @return Collection
     */
    public function pluck($value, ?string $key = null): self
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Run a map over each of the items.
     * @param callable $callback
     * @return Collection
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    /**
     * Run a map over each nested chunk of items.
     * @param callable $callback
     * @return Collection
     */
    public function mapSpread(callable $callback): self
    {
        return $this->map(static function ($chunk, $key) use ($callback) {
            $chunk[] = $key;
            return $callback(...$chunk);
        });
    }

    /**
     * Run a dictionary map over the items.
     * The callback should return an associative array with a single key/value pair.
     * @param callable $callback
     * @return Collection
     */
    public function mapToDictionary(callable $callback): self
    {
        $dictionary = [];
        foreach ($this->items as $key => $item) {
            $pair = $callback($item, $key);
            $key = key($pair);
            $value = reset($pair);
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $value;
        }
        return new static($dictionary);
    }

    /**
     * Run a grouping map over the items.
     * The callback should return an associative array with a single key/value pair.
     * @param callable $callback
     * @return Collection
     */
    public function mapToGroups(callable $callback): self
    {
        $groups = $this->mapToDictionary($callback);
        return $groups->map([$this, 'make']);
    }

    /**
     * Run an associative map over each of the items.
     * The callback should return an associative array with a single key/value pair.
     * @param callable $callback
     * @return Collection
     */
    public function mapWithKeys(callable $callback): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }
        return new static($result);
    }

    /**
     * Map a collection and flatten the result by a single level.
     * @param callable $callback
     * @return Collection
     */
    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Map the values into a new class.
     * @param string $class
     * @return Collection
     */
    public function mapInto(string $class): self
    {
        return $this->map(static function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    /**
     * Get the max value of a given key.
     *
     * @param null|callable|string $callback
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        return $this->filter(static function ($value) {
            return !is_null($value);
        })->reduce(static function ($result, $item) use ($callback) {
            $value = $callback($item);
            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * Merge the collection with the given items.
     * @param mixed $items
     * @return Collection
     */
    public function merge($items): self
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     * @param mixed $values
     * @return Collection
     */
    public function combine($values): self
    {
        return new static(array_combine($this->all(), $this->getArrayableItems($values)));
    }

    /**
     * Union the collection with the given items.
     * @param mixed $items
     * @return Collection
     */
    public function union($items): self
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * Get the min value of a given key.
     *
     * @param null|callable|string $callback
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);
        return $this->map(static function ($value) use ($callback) {
            return $callback($value);
        })->filter(static function ($value) {
            return !is_null($value);
        })->reduce(static function ($result, $value) {
            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * Create a new collection consisting of every n-th element.
     * @param int $step
     * @param int $offset
     * @return Collection
     */
    public function nth(int $step, int $offset = 0): self
    {
        $new = [];
        $position = 0;
        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            ++$position;
        }
        return new static($new);
    }

    /**
     * Get the items with the specified keys.
     * @param mixed $keys
     * @return Collection
     */
    public function only($keys): self
    {
        if (is_null($keys)) {
            return new static($this->items);
        }
        if ($keys instanceof self) {
            $keys = $keys->all();
        }
        $keys = is_array($keys) ? $keys : func_get_args();
        return new static(Arr::only($this->items, $keys));
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     * @param int $page
     * @param int $perPage
     * @return Collection
     */
    public function forPage(int $page, int $perPage): self
    {
        $offset = max(0, ($page - 1) * $perPage);
        return $this->slice($offset, $perPage);
    }

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param callable|string $key
     * @param null|mixed $operator
     * @param null|mixed $value
     * @return Collection
     */
    public function partition($key, $operator = null, $value = null): self
    {
        $partitions = [new static(), new static()];
        $callback = func_num_args() === 1 ? $this->valueRetriever($key) : $this->operatorForWhere(...func_get_args());
        foreach ($this->items as $key => $item) {
            $partitions[(int)!$callback($item, $key)][$key] = $item;
        }
        return new static($partitions);
    }

    /**
     * Pass the collection to the given callback and return the result.
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Get and remove the last item from the collection.
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     * @param null|mixed $key
     * @param mixed $value
     * @return Collection
     */
    public function prepend($value, $key = null): self
    {
        $this->items = Arr::prepend($this->items, $value, $key);
        return $this;
    }

    /**
     * Push an item onto the end of the collection.
     * @param mixed $value
     * @return Collection
     */
    public function push($value): self
    {
        $this->offsetSet(null, $value);
        return $this;
    }

    /**
     * Push all of the given items onto the collection.
     *
     * @param array|\Traversable $source
     * @return Collection
     */
    public function concat($source): self
    {
        $result = new static($this);
        foreach ($source as $item) {
            $result->push($item);
        }
        return $result;
    }

    /**
     * Get and remove an item from the collection.
     *
     * @param null|mixed $default
     * @param mixed $key
     * @return array|ArrayAccess|mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Put an item in the collection by key.
     * @param mixed $key
     * @param mixed $value
     * @return Collection
     */
    public function put($key, $value): self
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @param int|null $number
     * @return mixed|self
     */
    public function random(int $number = null)
    {
        if (is_null($number)) {
            return Arr::random($this->items);
        }
        return new static(Arr::random($this->items, $number));
    }

    /**
     * Reduce the collection to a single value.
     * @param callable $callback
     * @param null|mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param callable|mixed $callback
     * @return Collection
     */
    public function reject($callback): self
    {
        if ($this->useAsCallable($callback)) {
            return $this->filter(static function ($value, $key) use ($callback) {
                return !$callback($value, $key);
            });
        }
        return $this->filter(static function ($item) use ($callback) {
            return $item !== $callback;
        });
    }

    /**
     * Reverse items order.
     */
    public function reverse(): self
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     * @param mixed $value
     * @param bool $strict
     * @return bool|false|int|string
     */
    public function search($value, bool $strict = false)
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }
        foreach ($this->items as $key => $item) {
            if ($value($item, $key)) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Get and remove the first item from the collection.
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Shuffle the items in the collection.
     * @param int|null $seed
     * @return Collection
     */
    public function shuffle(int $seed = null): self
    {
        return new static(Arr::shuffle($this->items, $seed));
    }

    /**
     * Slice the underlying collection array.
     * @param int $offset
     * @param int|null $length
     * @return Collection
     */
    public function slice(int $offset, int $length = null): self
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Split a collection into a certain number of groups.
     * @param int $numberOfGroups
     * @return Collection
     */
    public function split(int $numberOfGroups): self
    {
        if ($this->isEmpty()) {
            return new static();
        }
        $groups = new static();
        $groupSize = floor($this->count() / $numberOfGroups);
        $remain = $this->count() % $numberOfGroups;
        $start = 0;
        for ($i = 0; $i < $numberOfGroups; ++$i) {
            $size = $groupSize;
            if ($i < $remain) {
                ++$size;
            }
            if ($size) {
                $groups->push(new static(array_slice($this->items, $start, $size)));
                $start += $size;
            }
        }
        return $groups;
    }

    /**
     * Chunk the underlying collection array.
     * @param int $size
     * @return Collection
     */
    public function chunk(int $size): self
    {
        if ($size <= 0) {
            return new static();
        }
        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }
        return new static($chunks);
    }

    /**
     * Sort through each item with a callback.
     * @param callable|null $callback
     * @return Collection
     */
    public function sort(callable $callback = null): self
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);
        return new static($items);
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param callable|string $callback
     * @param int $options
     * @param bool $descending
     * @return Collection
     */
    public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false): self
    {
        $results = [];
        $callback = $this->valueRetriever($callback);
        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }
        $descending ? arsort($results, $options) : asort($results, $options);
        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }
        return new static($results);
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param callable|string $callback
     * @param int $options
     * @return Collection
     */
    public function sortByDesc($callback, int $options = SORT_REGULAR): self
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * Sort the collection keys.
     * @param int $options
     * @param bool $descending
     * @return Collection
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): self
    {
        $items = $this->items;
        $descending ? krsort($items, $options) : ksort($items, $options);
        return new static($items);
    }

    /**
     * Sort the collection keys in descending order.
     * @param int $options
     * @return Collection
     */
    public function sortKeysDesc(int $options = SORT_REGULAR): self
    {
        return $this->sortKeys($options, true);
    }

    /**
     * Splice a portion of the underlying collection array.
     * @param int $offset
     * @param int|null $length
     * @param mixed $replacement
     * @return Collection
     */
    public function splice(int $offset, int $length = null, $replacement = []): self
    {
        if (func_num_args() === 1) {
            return new static(array_splice($this->items, $offset));
        }
        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Get the sum of the given values.
     *
     * @param null|callable|string $callback
     * @return float|int|mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }
        $callback = $this->valueRetriever($callback);
        return $this->reduce(static function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Take the first or last {$limit} items.
     * @param int $limit
     * @return Collection
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }
        return $this->slice(0, $limit);
    }

    /**
     * Pass the collection to the given callback and then return it.
     * @param callable $callback
     * @return Collection
     */
    public function tap(callable $callback): self
    {
        $callback(new static($this->items));
        return $this;
    }

    /**
     * Transform each item in the collection using a callback.
     * @param callable $callback
     * @return Collection
     */
    public function transform(callable $callback): self
    {
        $this->items = $this->map($callback)->all();
        return $this;
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param null|callable|string $key
     * @param bool $strict
     * @return Collection
     */
    public function unique($key = null, bool $strict = false): self
    {
        $callback = $this->valueRetriever($key);
        $exists = [];
        return $this->reject(static function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }
            $exists[] = $id;
        });
    }

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param null|callable|string $key
     * @return Collection
     */
    public function uniqueStrict($key = null): self
    {
        return $this->unique($key, true);
    }

    /**
     * Reset the keys on the underlying array.
     */
    public function values(): self
    {
        return new static(array_values($this->items));
    }

    /**
     * Zip the collection together with one or more arrays.
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]].
     *
     * @param mixed ...$items
     * @return Collection
     */
    public function zip($items): self
    {
        $arrayableItems = array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, func_get_args());
        $params = array_merge([
            static function () {
                return new static(func_get_args());
            },
            $this->items,
        ], $arrayableItems);
        return new static(array_map(...$params));
    }

    /**
     * Pad collection to the specified length with a value.
     * @param int $size
     * @param mixed $value
     * @return Collection
     */
    public function pad(int $size, $value): self
    {
        return new static(array_pad($this->items, $size, $value));
    }

    /**
     * Get the collection of items as a plain array.
     */
    public function toArray(): array
    {
        return array_map(static function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return array_map(static function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }
            if ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            }
            if ($value instanceof Arrayable) {
                return $value->toArray();
            }
            return $value;
        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get an iterator for the items.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get a CachingIterator instance.
     * @param int $flags
     * @return CachingIterator
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Count the number of items in the collection.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get a base Support collection instance from this collection.
     */
    public function toBase(): Collection
    {
        return new self($this);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Add a method to the list of proxied methods.
     * @param string $method
     */
    public static function proxy(string $method): void
    {
        static::$proxies[] = $method;
    }

    /**
     * Get an operator checker callback.
     * @param string $key
     * @param mixed|string $operator
     * @param null|mixed $value
     * @return Closure
     */
    protected function operatorForWhere(string $key, $operator = null, $value = null): Closure
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return static function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);
            $strings = array_filter([$retrieved, $value], static function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });
            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }
            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    /**
     * Determine if the given value is callable, but not a string.
     * @param mixed $value
     * @return bool
     */
    protected function useAsCallable($value): bool
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     * Get a value retrieving callback.
     * @param mixed $value
     * @return callable
     */
    protected function valueRetriever($value): callable
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        return static function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Results array of items from Collection or Arrayable.
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems($items): array
    {
        if (is_array($items)) {
            return $items;
        }
        if ($items instanceof self) {
            return $items->all();
        }
        if ($items instanceof Arrayable) {
            return $items->toArray();
        }
        if ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        }
        if ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        }
        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        return (array)$items;
    }
}
