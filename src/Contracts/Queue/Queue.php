<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Queue;

use DateInterval;
use DateTimeInterface;

interface Queue
{
    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     * @return int
     */
    public function size(string $queue = null): int;

    /**
     * Push a new job onto the queue.
     *
     * @param object|string $job
     * @param mixed|string $data
     * @param string|null $queue
     * @return mixed
     */
    public function push(object|string $job, mixed $data = '', string $queue = null): mixed;

    /**
     * Push a new job onto the queue.
     *
     * @param string $queue
     * @param object|string $job
     * @param mixed|string $data
     * @return mixed
     */
    public function pushOn(string $queue, object|string $job, mixed $data = ''): mixed;

    /**
     * Push a raw payload onto the queue.
     *
     * @param string $payload
     * @param string|null $queue
     * @param array $options
     * @return mixed
     */
    public function pushRaw(string $payload, string $queue = null, array $options = []): mixed;

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @param object|string $job
     * @param mixed|string $data
     * @param string|null $queue
     * @return mixed
     */
    public function later(DateInterval|DateTimeInterface|int $delay, object|string $job, mixed $data = '', string $queue = null): mixed;

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param string $queue
     * @param DateInterval|DateTimeInterface|int $delay
     * @param object|string $job
     * @param mixed|string $data
     * @return mixed
     */
    public function laterOn(string $queue, DateInterval|DateTimeInterface|int $delay, object|string $job, mixed $data = ''): mixed;

    /**
     * Push an array of jobs onto the queue.
     *
     * @param array $jobs
     * @param mixed|string $data
     * @param string|null $queue
     * @return mixed
     */
    public function bulk(array $jobs, mixed $data = '', string $queue = null): mixed;

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     * @return Job|null
     */
    public function pop(string $queue = null): ?Job;

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Set the connection name for the queue.
     *
     * @param string $name
     * @return $this
     */
    public function setConnectionName(string $name): self;
}
