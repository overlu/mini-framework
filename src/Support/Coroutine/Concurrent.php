<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Coroutine;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

/**
 * @method bool isFull()
 * @method bool isEmpty()
 * @method array stats()
 * @method int length()
 */
class Concurrent
{
    /**
     * @var Channel
     */
    protected Channel $channel;

    /**
     * @var int
     */
    protected int $limit;

    public function __construct(int $limit)
    {
        $this->limit = $limit;
        $this->channel = new Channel($limit);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, ['isFull', 'isEmpty', 'length', 'stats'])) {
            return $this->channel->{$name}(...$arguments);
        }
    }


    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getLength(): int
    {
        return $this->channel->length();
    }

    public function getRunningCoroutineCount(): int
    {
        return $this->getLength();
    }

    /**
     * @param callable $callable
     */
    public function create(callable $callable): void
    {
        $this->channel->push(true);
        Coroutine::create(function () use ($callable) {
            try {
                $callable();
            } catch (\Throwable $exception) {
                throw $exception;
            } finally {
                $this->channel->pop();
            }
        });
    }
}
