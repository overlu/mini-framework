<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Closure;
use Mini\Exception\ExceptionThrower;
use Mini\Exception\WaitTimeoutException;
use Mini\Support\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class Waiter
{
    /**
     * @var float
     */
    protected $pushTimeout = 10.0;

    /**
     * @var float
     */
    protected $popTimeout = 10.0;

    public function __construct(float $timeout = 10.0)
    {
        $this->popTimeout = $timeout;
    }

    /**
     * @param null|float $timeout seconds
     */
    public function wait(Closure $closure, ?float $timeout = null)
    {
        if ($timeout === null) {
            $timeout = $this->popTimeout;
        }

        $channel = new Channel(1);
        Coroutine::create(function () use ($channel, $closure) {
            try {
                $result = $closure();
            } catch (Throwable $exception) {
                $result = new ExceptionThrower($exception);
            } finally {
                $channel->push($result, $this->pushTimeout);
            }
        });

        $result = $channel->pop($timeout);
        if ($result === false && $channel->errCode === SWOOLE_CHANNEL_TIMEOUT) {
            throw new WaitTimeoutException(sprintf('Channel wait failed, reason: Timed out for %s s', $timeout));
        }
        if ($result instanceof ExceptionThrower) {
            throw $result->getThrowable();
        }

        return $result;
    }
}
