<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Concerns;

use Mini\Support\Arr;
use Mini\Support\LazyCollection;

trait ManagesLoops
{
    /**
     * The stack of in-progress loops.
     *
     * @var array
     */
    protected array $loopsStack = [];

    /**
     * Add new loop to the stack.
     *
     * @param \Countable|array $data
     * @return void
     */
    public function addLoop($data): void
    {
        $length = is_countable($data) && !$data instanceof LazyCollection
            ? count($data)
            : null;

        $parent = Arr::last($this->loopsStack);

        $this->loopsStack[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $length ?? null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length === 1 : null,
            'odd' => false,
            'even' => true,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $parent ? (object)$parent : null,
        ];
    }

    /**
     * Increment the top loop's indices.
     *
     * @return void
     */
    public function incrementLoopIndices(): void
    {
        $loop = $this->loopsStack[$index = count($this->loopsStack) - 1];

        $this->loopsStack[$index] = array_merge($this->loopsStack[$index], [
            'iteration' => $loop['iteration'] + 1,
            'index' => $loop['iteration'],
            'first' => $loop['iteration'] === 0,
            'odd' => !$loop['odd'],
            'even' => !$loop['even'],
            'remaining' => isset($loop['count']) ? $loop['remaining'] - 1 : null,
            'last' => isset($loop['count']) ? $loop['iteration'] === $loop['count'] - 1 : null,
        ]);
    }

    /**
     * Pop a loop from the top of the loop stack.
     *
     * @return void
     */
    public function popLoop(): void
    {
        array_pop($this->loopsStack);
    }

    /**
     * Get an instance of the last loop in the stack.
     *
     * @return \stdClass|null
     */
    public function getLastLoop(): ?\stdClass
    {
        if ($last = Arr::last($this->loopsStack)) {
            return (object)$last;
        }
        return null;
    }

    /**
     * Get the entire loop stack.
     *
     * @return array
     */
    public function getLoopStack(): array
    {
        return $this->loopsStack;
    }
}
