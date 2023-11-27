<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * @method static \Mini\Contracts\View\Factory addNamespace(string $namespace, string|array $hints)
 * @method static \Mini\Contracts\View\View first(array $views, \Mini\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static \Mini\Contracts\View\Factory replaceNamespace(string $namespace, string|array $hints)
 * @method static \Mini\Contracts\View\Factory addExtension(string $extension, string $engine, \Closure|null $resolver = null)
 * @method static \Mini\Contracts\View\View file(string $path, array $data = [], array $mergeData = [])
 * @method static \Mini\Contracts\View\View make(string $view, array $data = [], array $mergeData = [])
 * @method static array composer(array|string $views, \Closure|string $callback)
 * @method static array creator(array|string $views, \Closure|string $callback)
 * @method static bool exists(string $view)
 * @method static mixed share(array|string $key, $value = null)
 * @method static \Mini\View\ViewFinderInterface getFinder()
 *
 * @see \Mini\View\Factory
 */
class View extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'view';
    }
}
