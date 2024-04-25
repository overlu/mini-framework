<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Utils;

/**
 * @mixin Factory
 */
class Pool
{
    /**
     * The factory instance.
     *
     * @var null|Factory
     */
    protected ?Factory $factory;

    /**
     * The handler function for the Guzzle client.
     *
     * @var callable
     */
    protected $handler;

    /**
     * The pool of requests.
     *
     * @var array
     */
    protected array $pool = [];

    /**
     * Create a new requests pool.
     *
     * @param  Factory|null  $factory
     * @return void
     */
    public function __construct(Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();
        $this->handler = Utils::chooseHandler();
    }

    /**
     * Add a request to the pool with a key.
     *
     * @param  string  $key
     * @return PendingRequest
     */
    public function as(string $key): PendingRequest
    {
        return $this->pool[$key] = $this->asyncRequest();
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return PendingRequest
     */
    protected function asyncRequest(): PendingRequest
    {
        return $this->factory->setHandler($this->handler)->async();
    }

    /**
     * Retrieve the requests in the pool.
     *
     * @return PendingRequest[]
     */
    public function getRequests(): array
    {
        return $this->pool;
    }

    /**
     * Add a request to the pool with a numeric index.
     *
     * @param string $method
     * @param  array  $parameters
     * @return PendingRequest|Promise
     */
    public function __call(string $method, mixed $parameters)
    {
        return $this->pool[] = $this->asyncRequest()->$method(...$parameters);
    }
}
