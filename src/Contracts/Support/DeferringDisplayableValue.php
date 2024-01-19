<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return Htmlable|string
     */
    public function resolveDisplayableValue(): string|Htmlable;
}
