<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\View;

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
     * @param \Mini\Contracts\Support\Arrayable|array $data
     * @param array $mergeData
     * @return View|mixed
     */
    public function file(string $path, array $data = [], array $mergeData = []);

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param \Mini\Contracts\Support\Arrayable|array $data
     * @param array $mergeData
     * @return View|mixed
     */
    public function make(string $view, array $data = [], array $mergeData = []);

    /**
     * Add a piece of shared data to the environment.
     *
     * @param array|string $key
     * @param mixed $value
     * @return mixed
     */
    public function share($key, $value = null);

    /**
     * Register a view composer event.
     *
     * @param array|string $views
     * @param \Closure|string $callback
     * @return array
     */
    public function composer($views, $callback): array;

    /**
     * Register a view creator event.
     *
     * @param array|string $views
     * @param \Closure|string $callback
     * @return array
     */
    public function creator($views, $callback): array;

    /**
     * Add a new namespace to the loader.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return $this
     */
    public function addNamespace(string $namespace, $hints): self;

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return $this
     */
    public function replaceNamespace(string $namespace, $hints): self;
}
