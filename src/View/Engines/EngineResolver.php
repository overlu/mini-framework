<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Engines;

use Closure;
use InvalidArgumentException;

class EngineResolver
{
    /**
     * The array of engine resolvers.
     *
     * @var array
     */
    protected array $resolvers = [];

    /**
     * The resolved engine instances.
     *
     * @var array
     */
    protected array $resolved = [];

    /**
     * Register a new engine resolver.
     *
     * The engine string typically corresponds to a file extension.
     *
     * @param string $engine
     * @param \Closure $resolver
     * @return void
     */
    public function register(string $engine, Closure $resolver): void
    {
        $this->forget($engine);

        $this->resolvers[$engine] = $resolver;
    }

    /**
     * Resolve an engine instance by name.
     *
     * @param string $engine
     * @return \Mini\Contracts\View\Engine
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(string $engine): \Mini\Contracts\View\Engine
    {
        if (isset($this->resolved[$engine])) {
            return $this->resolved[$engine];
        }

        if (isset($this->resolvers[$engine])) {
            return $this->resolved[$engine] = call_user_func($this->resolvers[$engine]);
        }

        throw new InvalidArgumentException("Engine [{$engine}] not found.");
    }

    /**
     * Remove a resolved engine.
     *
     * @param string $engine
     * @return void
     */
    public function forget(string $engine): void
    {
        unset($this->resolved[$engine]);
    }
}
