<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Container;

use Countable;
use IteratorAggregate;

class RewindableGenerator implements Countable, IteratorAggregate
{
    /**
     * The generator callback.
     *
     * @var callable
     */
    protected $generator;

    /**
     * The number of tagged services.
     *
     * @var callable|int
     */
    protected $count;

    /**
     * Create a new generator instance.
     *
     * @param  callable  $generator
     * @param callable|int $count
     * @return void
     */
    public function __construct(callable $generator, callable|int $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }

    /**
     * Get an iterator from the generator.
     *
     * @return mixed
     */
    public function getIterator(): mixed
    {
        return ($this->generator)();
    }

    /**
     * Get the total number of tagged services.
     * @return int
     */
    public function count(): int
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }

        return $this->count;
    }
}
