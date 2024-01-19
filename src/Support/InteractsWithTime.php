<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;

trait InteractsWithTime
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @return int
     */
    protected function secondsUntil(DateInterval|DateTimeInterface|int $delay): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? max(0, $delay->getTimestamp() - $this->currentTime())
            : (int)$delay;
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @return int
     */
    protected function availableAt(DateInterval|DateTimeInterface|int $delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp()
            : Carbon::now()->addSeconds($delay)->getTimestamp();
    }

    /**
     * If the given value is an interval, convert it to a DateTime instance.
     *
     * @param DateInterval|DateTimeInterface|int $delay
     * @return DateInterval|DateTimeInterface|int
     */
    protected function parseDateInterval(DateInterval|DateTimeInterface|int $delay): DateInterval|DateTimeInterface|int
    {
        if ($delay instanceof DateInterval) {
            $delay = Carbon::now()->add($delay);
        }

        return $delay;
    }

    /**
     * Get the current system time as a UNIX timestamp.
     */
    protected function currentTime(): int
    {
        return Carbon::now()->getTimestamp();
    }
}
