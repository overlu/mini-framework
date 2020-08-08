<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Swoole\Coroutine\Channel;

class ChannelPool extends \SplQueue
{
    /**
     * @var ChannelPool
     */
    private static ChannelPool $instance;

    public static function getInstance(): self
    {
        return static::$instance ?? (static::$instance = new self());
    }

    public function get(): Channel
    {
        return $this->isEmpty() ? new Channel(1) : $this->pop();
    }

    public function release(Channel $channel): void
    {
        $channel->errCode = 0;
        $this->push($channel);
    }
}
