<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Pagination;

use ArrayAccess;
use Countable;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Htmlable;
use Mini\Contracts\Support\Jsonable;
use Mini\Support\Collection;
use IteratorAggregate;
use JsonSerializable;

class Paginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    protected bool $hasMore;

    /**
     * Create a new paginator instance.
     *
     * @param mixed $items
     * @param int $perPage
     * @param int|null $currentPage
     * @param array $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct(mixed $items, int $perPage, int $currentPage = null, array $options = [])
    {
        $this->options = $options;

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->perPage = $perPage;
        $this->currentPage = $this->setCurrentPage($currentPage);
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;

        $this->setItems($items);
    }

    /**
     * Get the current page for the request.
     *
     * @param int $currentPage
     * @return int
     */
    protected function setCurrentPage(int $currentPage): int
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage();

        return $this->isValidPageNumber($currentPage) ? (int)$currentPage : 1;
    }

    /**
     * Set the items for the paginator.
     *
     * @param mixed $items
     * @return void
     */
    protected function setItems(mixed $items): void
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);

        $this->hasMore = $this->items->count() > $this->perPage;

        $this->items = $this->items->slice(0, $this->perPage);
    }

    /**
     * Get the URL for the next page.
     *
     * @return string|null
     */
    public function nextPageUrl(): ?string
    {
        if ($this->hasMorePages()) {
            return $this->url($this->currentPage() + 1);
        }
        return null;
    }

    /**
     * Render the paginator using the given view.
     *
     * @param string|null $view
     * @param array $data
     * @return string|Htmlable
     */
    public function links(string $view = null, array $data = []): string|Htmlable
    {
        return $this->render($view, $data);
    }

    /**
     * Render the paginator using the given view.
     *
     * @param string|null $view
     * @param array $data
     * @return Htmlable
     */
    public function render(string $view = null, array $data = []): Htmlable
    {
        return static::viewFactory()->make($view ?: static::$defaultSimpleView, array_merge($data, [
            'paginator' => $this,
        ]));
    }

    /**
     * Manually indicate that the paginator does have more pages.
     *
     * @param bool $hasMore
     * @return $this
     */
    public function hasMorePagesWhen(bool $hasMore = true): static
    {
        $this->hasMore = $hasMore;

        return $this;
    }

    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage(),
            'data' => $this->items->toArray(),
            'first_page_url' => $this->url(1),
            'from' => $this->firstItem(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path(),
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
