<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View;

use Mini\Contracts\Support\Htmlable;

class ComponentSlot implements Htmlable
{
    /**
     * The slot attribute bag.
     *
     * @var ComponentAttributeBag
     */
    public ComponentAttributeBag $attributes;

    /**
     * The slot contents.
     *
     * @var string
     */
    protected string $contents;

    /**
     * Create a new slot instance.
     *
     * @param string $contents
     * @param array $attributes
     * @return void
     */
    public function __construct(string $contents = '', array $attributes = [])
    {
        $this->contents = $contents;

        $this->withAttributes($attributes);
    }

    /**
     * Set the extra attributes that the slot should make available.
     *
     * @param array $attributes
     * @return $this
     */
    public function withAttributes(array $attributes): self
    {
        $this->attributes = new ComponentAttributeBag($attributes);

        return $this;
    }

    /**
     * Get the slot's HTML string.
     *
     * @return string
     */
    public function toHtml(): string
    {
        return $this->contents;
    }

    /**
     * Determine if the slot is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->contents === '';
    }

    /**
     * Determine if the slot is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the slot's HTML string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }
}
