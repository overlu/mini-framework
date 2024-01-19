<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Container;

use Closure;

interface ContextualBindingBuilder
{
    /**
     * Define the abstract target that depends on the context.
     *
     * @param string $abstract
     * @return $this
     */
    public function needs(string $abstract): static;

    /**
     * Define the implementation for the contextual binding.
     *
     * @param string|Closure $implementation
     * @return void
     */
    public function give(string|Closure $implementation): void;
}
