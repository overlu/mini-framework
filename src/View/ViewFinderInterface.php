<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

interface ViewFinderInterface
{
    /**
     * Hint path delimiter value.
     *
     * @var string
     */
    public const HINT_PATH_DELIMITER = '::';

    /**
     * Get the fully qualified location of the view.
     *
     * @param string $view
     * @return string
     */
    public function find(string $view): string;

    /**
     * Add a location to the finder.
     *
     * @param string $location
     * @return void
     */
    public function addLocation(string $location): void;

    /**
     * Add a namespace hint to the finder.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return void
     */
    public function addNamespace(string $namespace, $hints): void;

    /**
     * Prepend a namespace hint to the finder.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return void
     */
    public function prependNamespace(string $namespace, $hints): void;

    /**
     * Replace the namespace hints for the given namespace.
     *
     * @param string $namespace
     * @param string|array $hints
     * @return void
     */
    public function replaceNamespace(string $namespace, $hints): void;

    /**
     * Add a valid view extension to the finder.
     *
     * @param string $extension
     * @return void
     */
    public function addExtension(string $extension): void;

    /**
     * Flush the cache of located views.
     *
     * @return void
     */
    public function flush(): void;
}
