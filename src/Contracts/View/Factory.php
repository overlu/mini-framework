<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\View;

use Closure;
use Mini\Contracts\Support\Arrayable;

interface Factory
{
    /**
     * Determine if a given view exists.
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool;

    /**
     * Get the evaluated view contents for the given path.
     *
     * @param string $path
     * @param array $data
     * @param array $mergeData
     * @return View|mixed
     */
    public function file(string $path, array $data = [], array $mergeData = []): mixed;

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return View|mixed
     */
    public function make(string $view, array $data = [], array $mergeData = []): mixed;

    /**
     * Add a piece of shared data to the environment.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return mixed
     */
    public function share(array|string $key, mixed $value = null): mixed;

    /**
     * Register a view composer event.
     *
     * @param array|string $views
     * @param string|Closure $callback
     * @return array
     */
    public function composer(array|string $views, string|Closure $callback): array;

    /**
     * Register a view creator event.
     *
     * @param array|string $views
     * @param string|Closure $callback
     * @return array
     */
    public function creator(array|string $views, string|Closure $callback): array;

    /**
     * Add a new namespace to the loader.
     *
     * @param string $namespace
     * @param array|string $hints
     * @return $this
     */
    public function addNamespace(string $namespace, array|string $hints): self;

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param string $namespace
     * @param array|string $hints
     * @return $this
     */
    public function replaceNamespace(string $namespace, array|string $hints): self;
}
