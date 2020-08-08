<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Engines;

abstract class Engine
{
    /**
     * The view that was last to be rendered.
     *
     * @var string
     */
    protected string $lastRendered;

    /**
     * Get the last view that was rendered.
     *
     * @return string
     */
    public function getLastRendered(): string
    {
        return $this->lastRendered;
    }
}
