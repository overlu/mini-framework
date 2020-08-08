<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Countable;
use Mini\Contracts\Support\MessageBag as MessageBagContract;

/**
 * @mixin MessageBagContract
 */
class ViewErrorBag implements Countable
{
    /**
     * The array of the view error bags.
     *
     * @var array
     */
    protected array $bags = [];

    /**
     * Checks if a named MessageBag exists in the bags.
     *
     * @param string $key
     * @return bool
     */
    public function hasBag(string $key = 'default'): bool
    {
        return isset($this->bags[$key]);
    }

    /**
     * Get a MessageBag instance from the bags.
     *
     * @param string $key
     * @return MessageBagContract
     */
    public function getBag(string $key): MessageBagContract
    {
        return Arr::get($this->bags, $key) ?: new MessageBag;
    }

    /**
     * Get all the bags.
     *
     * @return array
     */
    public function getBags(): array
    {
        return $this->bags;
    }

    /**
     * Add a new MessageBag instance to the bags.
     *
     * @param string $key
     * @param MessageBagContract $bag
     * @return $this
     */
    public function put(string $key, MessageBagContract $bag): self
    {
        $this->bags[$key] = $bag;

        return $this;
    }

    /**
     * Determine if the default message bag has any messages.
     *
     * @return bool
     */
    public function any(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get the number of messages in the default bag.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->getBag('default')->count();
    }

    /**
     * Dynamically call methods on the default bag.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->getBag('default')->$method(...$parameters);
    }

    /**
     * Dynamically access a view error bag.
     *
     * @param string $key
     * @return MessageBagContract
     */
    public function __get($key)
    {
        return $this->getBag($key);
    }

    /**
     * Dynamically set a view error bag.
     *
     * @param string $key
     * @param MessageBagContract $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->put($key, $value);
    }

    /**
     * Convert the default bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getBag('default');
    }
}
