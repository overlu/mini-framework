<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\View;

use Mini\Contracts\Support\Renderable;

interface View extends Renderable
{
    /**
     * Get the name of the view.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Add a piece of data to the view.
     *
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function with(array|string $key, mixed $value = null): self;

    /**
     * Get the array of view data.
     *
     * @return array
     */
    public function getData(): array;
}
