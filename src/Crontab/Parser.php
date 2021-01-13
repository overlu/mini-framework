<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Crontab;

/**
 * Class Parser
 * @package Mini\Crontab
 */
class Parser
{
    /**
     *  Finds next execution time(stamp) parsin crontab syntax.
     *
     * @param string $crontab_string :
     *   0    1    2    3    4    5
     *   *    *    *    *    *    *
     *   -    -    -    -    -    -
     *   |    |    |    |    |    |
     *   |    |    |    |    |    +----- day of week (0 - 6) (Sunday=0)
     *   |    |    |    |    +----- month (1 - 12)
     *   |    |    |    +------- day of month (1 - 31)
     *   |    |    +--------- hour (0 - 23)
     *   |    +----------- min (0 - 59)
     *   +------------- sec (0-59)
     *
     * @param null|int $start_time
     * @return int[]
     * @throws \InvalidArgumentException
     */
    public static function parse($crontab_string, ?int $start_time = null)
    {
        if (!self::isValid($crontab_string)) {
            throw new \InvalidArgumentException('Invalid cron string: ' . $crontab_string);
        }
        $start_time = $start_time ? $start_time : time();
        $date = self::parseDate($crontab_string);
        if (in_array((int)date('i', $start_time), $date['minutes'])
            && in_array((int)date('G', $start_time), $date['hours'])
            && in_array((int)date('j', $start_time), $date['day'])
            && in_array((int)date('w', $start_time), $date['week'])
            && in_array((int)date('n', $start_time), $date['month'])
        ) {
            $result = [];
            foreach ($date['second'] as $second) {
                $result[] = $start_time + $second;
            }
            return $result;
        }
        return [];
    }

    public static function isValid(string $crontab_string): bool
    {
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontab_string))) {
            if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($crontab_string))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse each segment of crontab string.
     */
    protected static function parseSegment(string $string, int $min, int $max, int $start = null)
    {
        if ($start === null || $start < $min) {
            $start = $min;
        }
        $result = [];
        if ($string === '*') {
            for ($i = $start; $i <= $max; ++$i) {
                $result[] = $i;
            }
        } elseif (strpos($string, ',') !== false) {
            $exploded = explode(',', $string);
            foreach ($exploded as $value) {
                if (!self::between((int)$value, (int)($min > $start ? $min : $start), (int)$max)) {
                    continue;
                }
                $result[] = (int)$value;
            }
        } elseif (strpos($string, '/') !== false) {
            $exploded = explode('/', $string);
            if (strpos($exploded[0], '-') !== false) {
                [$nMin, $nMax] = explode('-', $exploded[0]);
                $nMin > $min && $min = $nMin;
                $nMax < $max && $max = $nMax;
            }
            $start > $min && $min = $start;
            for ($i = $start; $i <= $max;) {
                $result[] = $i;
                $i += $exploded[1];
            }
        } elseif (self::between((int)$string, $min > $start ? $min : $start, $max)) {
            $result[] = (int)$string;
        }
        return $result;
    }

    /**
     * Determire if the $value is between in $min and $max ?
     */
    private static function between(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }


    private static function parseDate(string $crontab_string): array
    {
        $cron = preg_split('/[\\s]+/i', trim($crontab_string));
        if (count($cron) === 6) {
            $date = [
                'second' => self::parseSegment($cron[0], 0, 59),
                'minutes' => self::parseSegment($cron[1], 0, 59),
                'hours' => self::parseSegment($cron[2], 0, 23),
                'day' => self::parseSegment($cron[3], 1, 31),
                'month' => self::parseSegment($cron[4], 1, 12),
                'week' => self::parseSegment($cron[5], 0, 6),
            ];
        } else {
            $date = [
                'second' => [0],
                'minutes' => self::parseSegment($cron[0], 0, 59),
                'hours' => self::parseSegment($cron[1], 0, 23),
                'day' => self::parseSegment($cron[2], 1, 31),
                'month' => self::parseSegment($cron[3], 1, 12),
                'week' => self::parseSegment($cron[4], 0, 6),
            ];
        }
        return $date;
    }
}