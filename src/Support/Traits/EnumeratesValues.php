<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits;

use CachingIterator;
use Closure;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Support\Arr;
use Mini\Support\Collection;
use Mini\Support\Enumerable;
use Mini\Support\HigherOrderCollectionProxy;
use Mini\Support\HigherOrderWhenProxy;
use JsonSerializable;
use Mini\Support\LazyCollection;
use Symfony\Component\VarDumper\VarDumper;
use Traversable;

/**
 * @property-read HigherOrderCollectionProxy $average
 * @property-read HigherOrderCollectionProxy $avg
 * @property-read HigherOrderCollectionProxy $contains
 * @property-read HigherOrderCollectionProxy $each
 * @property-read HigherOrderCollectionProxy $every
 * @property-read HigherOrderCollectionProxy $filter
 * @property-read HigherOrderCollectionProxy $first
 * @property-read HigherOrderCollectionProxy $flatMap
 * @property-read HigherOrderCollectionProxy $groupBy
 * @property-read HigherOrderCollectionProxy $keyBy
 * @property-read HigherOrderCollectionProxy $map
 * @property-read HigherOrderCollectionProxy $max
 * @property-read HigherOrderCollectionProxy $min
 * @property-read HigherOrderCollectionProxy $partition
 * @property-read HigherOrderCollectionProxy $reject
 * @property-read HigherOrderCollectionProxy $some
 * @property-read HigherOrderCollectionProxy $sortBy
 * @property-read HigherOrderCollectionProxy $sortByDesc
 * @property-read HigherOrderCollectionProxy $sum
 * @property-read HigherOrderCollectionProxy $unique
 * @property-read HigherOrderCollectionProxy $until
 */
trait EnumeratesValues
{
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
        'skipUntil',
        'skipWhile',
        'some',
        'sortBy',
        'sortByDesc',
        'sum',
        'takeUntil',
        'takeWhile',
        'unique',
        'until',
    ];

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param mixed|array $items
     * @return static
     */
    public static function make(mixed $items = [])
    {
        return new static($items);
    }

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @param mixed $value
     * @return static
     */
    public static function wrap(mixed $value): static
    {
        return $value instanceof Enumerable
            ? new static($value)
            : new static(Arr::wrap($value));
    }

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @param array|Enumerable $value
     * @return array
     */
    public static function unwrap(Enumerable|array $value): array
    {
        return $value instanceof Enumerable ? $value->all() : $value;
    }

    /**
     * Alias for the "avg" method.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function average(callable|string $callback = null): mixed
    {
        return $this->avg($callback);
    }

    /**
     * Alias for the "contains" method.
     *
     * @param mixed $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return bool
     */
    public function some(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        return $this->contains(...func_get_args());
    }

    /**
     * Determine if an item exists, using strict comparison.
     *
     * @param mixed $key
     * @param mixed|null $value
     * @return bool
     */
    public function containsStrict(mixed $key, mixed $value = null): bool
    {
        if (func_num_args() === 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return data_get($item, $key) === $value;
            });
        }

        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }

        foreach ($this as $item) {
            if ($item === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dump the items and end the script.
     *
     * @param mixed ...$args
     * @return void
     */
    public function dd(...$args): void
    {
        call_user_func_array([$this, 'dump'], $args);
    }

    /**
     * Dump the items.
     *
     * @return Collection|LazyCollection|EnumeratesValues
     */
    public function dump(): self
    {
        (new static(func_get_args()))
            ->push($this)
            ->each(static function ($item) {
                VarDumper::dump($item);
            });

        return $this;
    }

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     * @return Collection|LazyCollection|EnumeratesValues
     */
    public function each(callable $callback): self
    {
        foreach ($this as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Execute a callback over each nested chunk of items.
     *
     * @param callable $callback
     * @return static
     */
    public function eachSpread(callable $callback): static
    {
        return $this->each(static function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Determine if all items pass the given truth test.
     *
     * @param callable|string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return bool
     */
    public function every(callable|string $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);

            foreach ($this as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get the first item by the given key value pair.
     *
     * @param string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return mixed
     */
    public function firstWhere(string $key, mixed $operator = null, mixed $value = null): mixed
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Run a map over each nested chunk of items.
     *
     * @param callable $callback
     * @return static
     */
    public function mapSpread(callable $callback): static
    {
        return $this->map(static function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Run a grouping map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     * @return static
     */
    public function mapToGroups(callable $callback): static
    {
        return $this->mapToDictionary($callback)->map([$this, 'make']);
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable $callback
     * @return static
     */
    public function flatMap(callable $callback): static
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Map the values into a new class.
     *
     * @param string $class
     * @return static
     */
    public function mapInto(string $class): static
    {
        return $this->map(static function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    /**
     * Get the min value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function min(callable|string $callback = null): mixed
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
     * Get the max value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function max(callable|string $callback = null): mixed
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
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param int $page
     * @param int $perPage
     * @return static
     */
    public function forPage(int $page, int $perPage): static
    {
        $offset = max(0, ($page - 1) * $perPage);

        return $this->slice($offset, $perPage);
    }

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param callable|string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return static
     */
    public function partition(callable|string $key, mixed $operator = null, mixed $value = null): static
    {
        $passed = [];
        $failed = [];

        $callback = func_num_args() === 1
            ? $this->valueRetriever($key)
            : $this->operatorForWhere(...func_get_args());

        foreach ($this as $k => $item) {
            if ($callback($item, $k)) {
                $passed[$k] = $item;
            } else {
                $failed[$k] = $item;
            }
        }

        return new static([new static($passed), new static($failed)]);
    }

    /**
     * Get the sum of the given values.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function sum(callable|string $callback = null): mixed
    {
        if (is_null($callback)) {
            $callback = static function ($value) {
                return $value;
            };
        } else {
            $callback = $this->valueRetriever($callback);
        }

        return $this->reduce(static function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function when(bool $value, callable $callback, callable $default = null): mixed
    {
        if (!$callback) {
            return new HigherOrderWhenProxy($this, $value);
        }

        if ($value) {
            return $callback($this, $value);
        }

        if ($default) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * Apply the callback if the collection is empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function whenEmpty(callable $callback, callable $default = null): mixed
    {
        return $this->when($this->isEmpty(), $callback, $default);
    }

    /**
     * Apply the callback if the collection is not empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function whenNotEmpty(callable $callback, callable $default = null): mixed
    {
        return $this->when($this->isNotEmpty(), $callback, $default);
    }

    /**
     * Apply the callback if the value is falsy.
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unless(bool $value, callable $callback, callable $default = null): mixed
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * Apply the callback unless the collection is empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unlessEmpty(callable $callback, callable $default = null): mixed
    {
        return $this->whenNotEmpty($callback, $default);
    }

    /**
     * Apply the callback unless the collection is not empty.
     *
     * @param callable $callback
     * @param callable|null $default
     * @return static|mixed
     */
    public function unlessNotEmpty(callable $callback, callable $default = null): mixed
    {
        return $this->whenEmpty($callback, $default);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed|null $operator
     * @param mixed|null $value
     * @return static
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Filter items where the given key is not null.
     *
     * @param string|null $key
     * @return static
     */
    public function whereNull(string $key = null): static
    {
        return $this->whereStrict($key, null);
    }

    /**
     * Filter items where the given key is null.
     *
     * @param string|null $key
     * @return static
     */
    public function whereNotNull(string $key = null): static
    {
        return $this->where($key, '!==', null);
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function whereStrict(string $key, mixed $value): static
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereIn(string $key, mixed $values, bool $strict = false): static
    {
        $values = $this->getArrayableItems($values);

        return $this->filter(static function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed $values
     * @return static
     */
    public function whereInStrict(string $key, mixed $values): static
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items such that the value of the given key is between the given values.
     *
     * @param string $key
     * @param array $values
     * @return static
     */
    public function whereBetween(string $key, array $values): static
    {
        return $this->where($key, '>=', reset($values))->where($key, '<=', end($values));
    }

    /**
     * Filter items such that the value of the given key is not between the given values.
     *
     * @param string $key
     * @param array $values
     * @return static
     */
    public function whereNotBetween(string $key, array $values): static
    {
        return $this->filter(static function ($item) use ($key, $values) {
            return data_get($item, $key) < reset($values) || data_get($item, $key) > end($values);
        });
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed $values
     * @param bool $strict
     * @return static
     */
    public function whereNotIn(string $key, mixed $values, bool $strict = false): static
    {
        $values = $this->getArrayableItems($values);

        return $this->reject(static function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed $values
     * @return static
     */
    public function whereNotInStrict(string $key, mixed $values): static
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filter the items, removing any items that don't match the given type.
     *
     * @param string $type
     * @return static
     */
    public function whereInstanceOf(string $type): static
    {
        return $this->filter(static function ($value) use ($type) {
            return $value instanceof $type;
        });
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param callable $callback
     * @return Collection|LazyCollection|EnumeratesValues
     */
    public function tap(callable $callback): self
    {
        $callback(clone $this);

        return $this;
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param mixed|bool $callback
     * @return static
     */
    public function reject(mixed $callback = true): static
    {
        $useAsCallable = $this->useAsCallable($callback);

        return $this->filter(static function ($value, $key) use ($callback, $useAsCallable) {
            return $useAsCallable
                ? !$callback($value, $key)
                : $value !== $callback;
        });
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param callable|string|null $key
     * @param bool $strict
     * @return static
     */
    public function unique(callable|string $key = null, bool $strict = false): static
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
     * @param callable|string|null $key
     * @return static
     */
    public function uniqueStrict(callable|string $key = null): static
    {
        return $this->unique($key, true);
    }

    /**
     * Take items in the collection until the given condition is met.
     *
     * This is an alias to the "takeUntil" method.
     *
     * @param $value
     * @return static
     *
     * @deprecated Use the "takeUntil" method directly.
     */
    public function until($value): static
    {
        return $this->takeUntil($value);
    }

    /**
     * Collect the values into a collection.
     *
     * @return Collection
     */
    public function collect(): Collection
    {
        return new Collection($this->all());
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->map(static function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        })->all();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
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
        }, $this->all());
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get a CachingIterator instance.
     *
     * @param int $flags
     * @return CachingIterator
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator
    {
        return new CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Count the number of items in the collection using a given truth test.
     *
     * @param callable|null $callback
     * @return static
     */
    public function countBy(callable $callback = null): static
    {
        if (is_null($callback)) {
            $callback = static function ($value) {
                return $value;
            };
        }

        return new static($this->groupBy($callback)->map(function ($value) {
            return $value->count();
        }));
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Add a method to the list of proxied methods.
     *
     * @param string $method
     * @return void
     */
    public static function proxy(string $method): void
    {
        static::$proxies[] = $method;
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     * @return HigherOrderCollectionProxy
     *
     * @throws \Exception
     */
    public function __get(string $key)
    {
        if (!in_array($key, static::$proxies, true)) {
            throw new \RuntimeException("Property [{$key}] does not exist on this collection instance.");
        }

        return new HigherOrderCollectionProxy($this, $key);
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems(mixed $items): array
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof Enumerable) {
            return $items->all();
        }

        if ($items instanceof Arrayable) {
            return $items->toArray();
        }

        if ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        }

        if ($items instanceof JsonSerializable) {
            return (array)$items->jsonSerialize();
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array)$items;
    }

    /**
     * Get an operator checker callback.
     *
     * @param string $key
     * @param string|null $operator
     * @param mixed|null $value
     * @return callable|Closure
     */
    protected function operatorForWhere(string $key, string $operator = null, mixed $value = null): callable|Closure
    {
        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) === 1) {
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
     *
     * @param mixed $value
     * @return bool
     */
    protected function useAsCallable(mixed $value): bool
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     * Get a value retrieving callback.
     *
     * @param callable|string|null $value
     * @return callable|Closure|string|null
     */
    protected function valueRetriever(callable|string|null $value): callable|Closure|string|null
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return static function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Make a function to check an item's equality.
     *
     * @param mixed $value
     * @return Closure
     */
    protected function equality(mixed $value): callable
    {
        return static function ($item) use ($value) {
            return $item === $value;
        };
    }

    /**
     * Make a function using another function, by negating its result.
     *
     * @param Closure $callback
     * @return Closure
     */
    protected function negate(Closure $callback): callable
    {
        return static function (...$params) use ($callback) {
            return !$callback(...$params);
        };
    }
}
