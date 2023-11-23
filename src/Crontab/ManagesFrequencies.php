<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

use Mini\Support\Carbon;

trait ManagesFrequencies
{
    /**
     * The Cron expression representing the event's frequency.
     *
     * @param string $expression
     * @return $this
     */
    public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run every second.
     * @return string
     */
    public function everySecond(): string
    {
        return $this->spliceIntoPosition(1, '*')->expression;
    }

    /**
     * Schedule the event to run every two seconds.
     * @return string
     */
    public function everyTwoSeconds(): string
    {
        return $this->spliceIntoPosition(1, '*/2')->expression;
    }

    /**
     * Schedule the event to run every three seconds.
     * @return string
     */
    public function everyThreeSeconds(): string
    {
        return $this->spliceIntoPosition(1, '*/3')->expression;
    }

    /**
     * Schedule the event to run every four seconds.
     * @return string
     */
    public function everyFourSeconds(): string
    {
        return $this->spliceIntoPosition(1, '*/4')->expression;
    }

    /**
     * Schedule the event to run every five seconds.
     * @return string
     */
    public function everyFiveSeconds(): string
    {
        return $this->spliceIntoPosition(1, '*/5')->expression;
    }

    /**
     * Schedule the event to run every ten seconds.
     * @return string
     */
    public function everyTenSeconds(): string
    {
        return $this->spliceIntoPosition(1, '*/10')->expression;
    }

    /**
     * Schedule the event to run every fifteen seconds.
     * @return string
     */
    public function everyFifteenSeconds(): string
    {
        return $this->spliceIntoPosition(1, '*/15')->expression;
    }

    /**
     * Schedule the event to run every thirty seconds.
     * @return string
     */
    public function everyThirtySeconds(): string
    {
        return $this->spliceIntoPosition(1, '0,30')->expression;
    }

    /**
     * Schedule the event to run every minute.
     * @return string
     */
    public function everyMinute(): string
    {
        return $this->spliceIntoPosition(2, '*')->expression;
    }

    /**
     * Schedule the event to run every two minutes.
     * @return string
     */
    public function everyTwoMinutes(): string
    {
        return $this->spliceIntoPosition(2, '*/2')->expression;
    }

    /**
     * Schedule the event to run every three minutes.
     * @return string
     */
    public function everyThreeMinutes(): string
    {
        return $this->spliceIntoPosition(2, '*/3')->expression;
    }

    /**
     * Schedule the event to run every four minutes.
     * @return string
     */
    public function everyFourMinutes(): string
    {
        return $this->spliceIntoPosition(2, '*/4')->expression;
    }

    /**
     * Schedule the event to run every five minutes.
     * @return string
     */
    public function everyFiveMinutes(): string
    {
        return $this->spliceIntoPosition(2, '*/5')->expression;
    }

    /**
     * Schedule the event to run every ten minutes.
     * @return string
     */
    public function everyTenMinutes(): string
    {
        return $this->spliceIntoPosition(2, '*/10')->expression;
    }

    /**
     * Schedule the event to run every fifteen minutes.
     * @return string
     */
    public function everyFifteenMinutes(): string
    {
        return $this->spliceIntoPosition(2, '*/15')->expression;
    }

    /**
     * Schedule the event to run every thirty minutes.
     * @return string
     */
    public function everyThirtyMinutes(): string
    {
        return $this->spliceIntoPosition(2, '0,30')->expression;
    }

    /**
     * Schedule the event to run hourly.
     * @return string
     */
    public function hourly(): string
    {
        return $this->spliceIntoPosition(2, 0)->expression;
    }

    /**
     * Schedule the event to run hourly at a given offset in the hour.
     *
     * @param array|int $offset
     * @return string
     */
    public function hourlyAt($offset): string
    {
        $time = is_array($offset) ? implode(',', $offset) : $offset;

        return $this->spliceIntoPosition(2, $time)->expression;
    }

    /**
     * Schedule the event to run every two hours.
     * @return string
     */
    public function everyTwoHours(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, '*/2')->expression;
    }

    /**
     * Schedule the event to run every three hours.
     * @return string
     */
    public function everyThreeHours(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, '*/3')->expression;
    }

    /**
     * Schedule the event to run every four hours.
     * @return string
     */
    public function everyFourHours(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, '*/4')->expression;
    }

    /**
     * Schedule the event to run every six hours.
     * @return string
     */
    public function everySixHours(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, '*/6')->expression;
    }

    /**
     * Schedule the event to run daily.
     * @return string
     */
    public function daily(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 0)->expression;
    }

    /**
     * Schedule the command at a given time.
     * @param string $time
     * @return string
     */
    public function at(string $time): string
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param string $time
     * @return string
     */
    public function dailyAt(string $time): string
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(3, (int)$segments[0])
            ->spliceIntoPosition(2, count($segments) === 2 ? (int)$segments[1] : '0')->expression;
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @param int $first
     * @param int $second
     * @return string
     */
    public function twiceDaily(int $first = 1, int $second = 13): string
    {
        return $this->twiceDailyAt($first, $second, 0);
    }

    /**
     * Schedule the event to run twice daily at a given offset.
     * @param int $first
     * @param int $second
     * @param int $offset
     * @return string
     */
    public function twiceDailyAt(int $first = 1, int $second = 13, int $offset = 0): string
    {
        $hours = $first . ',' . $second;

        return $this->spliceIntoPosition(2, $offset)
            ->spliceIntoPosition(3, $hours)->expression;
    }

    /**
     * Schedule the event to run only on weekdays.
     * @return string
     */
    public function weekdays(): string
    {
        return $this->days(self::MONDAY . '-' . self::FRIDAY);
    }

    /**
     * Schedule the event to run only on weekends.
     * @return string
     */
    public function weekends(): string
    {
        return $this->days(self::SATURDAY . ',' . self::SUNDAY);
    }

    /**
     * Schedule the event to run only on Mondays.
     * @return string
     */
    public function mondays(): string
    {
        return $this->days(self::MONDAY);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     * @return string
     */
    public function tuesdays(): string
    {
        return $this->days(self::TUESDAY);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     * @return string
     */
    public function wednesdays(): string
    {
        return $this->days(self::WEDNESDAY);
    }

    /**
     * Schedule the event to run only on Thursdays.
     * @return string
     */
    public function thursdays(): string
    {
        return $this->days(self::THURSDAY);
    }

    /**
     * Schedule the event to run only on Fridays.
     * @return string
     */
    public function fridays(): string
    {
        return $this->days(self::FRIDAY);
    }

    /**
     * Schedule the event to run only on Saturdays.
     * @return string
     */
    public function saturdays(): string
    {
        return $this->days(self::SATURDAY);
    }

    /**
     * Schedule the event to run only on Sundays.
     * @return string
     */
    public function sundays(): string
    {
        return $this->days(self::SUNDAY);
    }

    /**
     * Schedule the event to run weekly.
     * @return string
     */
    public function weekly(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 0)
            ->spliceIntoPosition(6, 0)->expression;
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param array|mixed $dayOfWeek
     * @param string $time
     * @return string
     */
    public function weeklyOn($dayOfWeek, string $time = '0:0'): string
    {
        $this->dailyAt($time);

        return $this->days($dayOfWeek);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return string
     */
    public function monthly(): string
    {
        return $this->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 0)
            ->spliceIntoPosition(4, 1)->expression;
    }

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * @param int $dayOfMonth
     * @param string $time
     * @return string
     */
    public function monthlyOn(int $dayOfMonth = 1, string $time = '0:0'): string
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(4, $dayOfMonth)->expression;
    }

    /**
     * Schedule the event to run twice monthly at a given time.
     *
     * @param int $first
     * @param int $second
     * @param string $time
     * @return string
     */
    public function twiceMonthly(int $first = 1, int $second = 16, string $time = '0:0'): string
    {
        $daysOfMonth = $first . ',' . $second;

        $this->dailyAt($time);

        return $this->spliceIntoPosition(4, $daysOfMonth)->expression;
    }

    /**
     * Schedule the event to run on the last day of the month.
     *
     * @param string $time
     * @return string
     */
    public function lastDayOfMonth(string $time = '0:0'): string
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(4, Carbon::now()->endOfMonth()->day)->expression;
    }

    /**
     * Schedule the event to run quarterly.
     * @return string
     */
    public function quarterly(): string
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 0)
            ->spliceIntoPosition(4, 1)
            ->spliceIntoPosition(5, '1-12/3')
            ->expression;
    }

    /**
     * Schedule the event to run yearly.
     * @return string
     */
    public function yearly(): string
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 0)
            ->spliceIntoPosition(4, 1)
            ->spliceIntoPosition(5, 1)
            ->expression;
    }

    /**
     * Schedule the event to run yearly on a given month, day, and time.
     *
     * @param int $month
     * @param int|string $dayOfMonth
     * @param string $time
     * @return string
     */
    public function yearlyOn(int $month = 1, $dayOfMonth = 1, string $time = '0:0'): string
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(4, $dayOfMonth)
            ->spliceIntoPosition(5, $month)
            ->expression;
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param array|mixed $days
     */
    public function days($days): string
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(6, implode(',', $days))->expression;
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param int $position
     * @param string|int $value
     * @return $this
     */
    protected function spliceIntoPosition(int $position, $value): self
    {
        $segments = explode(' ', $this->expression);

        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }
}
