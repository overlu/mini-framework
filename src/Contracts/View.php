<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use Mini\Contracts\View\Factory;

/**
 * @method \Mini\Contracts\View addNamespace(string $namespace, string|array $hints)
 * @method \Mini\Contracts\View\View first(array $views, \Mini\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method \Mini\Contracts\View replaceNamespace(string $namespace, string|array $hints)
 * @method \Mini\Contracts\View addExtension(string $extension, string $engine, \Closure|null $resolver = null)
 * @method \Mini\Contracts\View\View file(string $path, array $data = [], array $mergeData = [])
 * @method \Mini\Contracts\View\View make(string $view, array $data = [], array $mergeData = [])
 * @method array composer(array|string $views, \Closure|string $callback)
 * @method array creator(array|string $views, \Closure|string $callback)
 * @method bool exists(string $view)
 * @method mixed share(array|string $key, $value = null)
 * @method \Mini\View\ViewFinderInterface getFinder()
 *
 * @see \Mini\View\Factory
 */
interface View extends Factory
{

}
