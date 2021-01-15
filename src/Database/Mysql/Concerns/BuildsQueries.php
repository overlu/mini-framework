<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Database\Mysql\Concerns;

use Mini\Container\Container;
use Mini\Contracts\Container\BindingResolutionException;
use Mini\Database\Mysql\Eloquent\Model;
use Mini\Pagination\LengthAwarePaginator;
use Mini\Pagination\Paginator;
use Mini\Support\Collection;

trait BuildsQueries
{
    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     * @return bool
     */
    public function chunk(int $count, callable $callback): bool
    {
        $this->enforceOrderBy();

        $page = 1;

        do {
            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            $results = $this->forPage($page, $count)->get();

            $countResults = $results->count();

            if ($countResults === 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);

            $page++;
        } while ($countResults === $count);

        return true;
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param callable $callback
     * @param int $count
     * @return bool
     */
    public function each(callable $callback, int $count = 1000): bool
    {
        return $this->chunk($count, static function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    /**
     * Chunk the results of a query by comparing IDs.
     *
     * @param int $count
     * @param callable $callback
     * @param string|null $column
     * @param string|null $alias
     * @return bool
     */
    public function chunkById(int $count, callable $callback, ?string $column = null, ?string $alias = null): bool
    {
        $column = $column ?? $this->defaultKeyName();

        $alias = $alias ?? $column;

        $lastId = null;

        do {
            $clone = clone $this;

            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            $results = $clone->forPageAfterId($count, $lastId, $column)->get();

            $countResults = $results->count();

            if ($countResults === 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results) === false) {
                return false;
            }

            $lastId = $results->last()->{$alias};

            unset($results);
        } while ($countResults === $count);

        return true;
    }

    /**
     * Execute a callback over each item while chunking by ID.
     *
     * @param callable $callback
     * @param int $count
     * @param string|null $column
     * @param string|null $alias
     * @return bool
     */
    public function eachById(callable $callback, int $count = 1000, ?string $column = null, ?string $alias = null): bool
    {
        return $this->chunkById($count, static function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        }, $column, $alias);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param array|string $columns
     * @return Model|object|static|null
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->get($columns)->first();
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return mixed|$this
     */
    public function when($value, callable $callback, ?callable $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }

        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Pass the query to a given callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function tap(callable $callback): self
    {
        return $this->when(true, $callback);
    }

    /**
     * Apply the callback's query changes if the given "value" is false.
     *
     * @param mixed $value
     * @param callable $callback
     * @param callable|null $default
     * @return mixed|$this
     */
    public function unless($value, callable $callback, ?callable $default = null)
    {
        if (!$value) {
            return $callback($this, $value) ?: $this;
        }

        if ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Create a new length-aware paginator instance.
     *
     * @param Collection $items
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @param array $options
     * @return LengthAwarePaginator
     * @throws BindingResolutionException
     */
    protected function paginator(Collection $items, int $total, int $perPage, int $currentPage, array $options): LengthAwarePaginator
    {
        return Container::getInstance()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }

    /**
     * Create a new simple paginator instance.
     *
     * @param Collection $items
     * @param int $perPage
     * @param int $currentPage
     * @param array $options
     * @return Paginator
     * @throws BindingResolutionException
     */
    protected function simplePaginator(Collection $items, int $perPage, int $currentPage, array $options): Paginator
    {
        return Container::getInstance()->makeWith(Paginator::class, compact(
            'items', 'perPage', 'currentPage', 'options'
        ));
    }
}
