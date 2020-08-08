<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

class Math
{
    /**
     * @param $value
     * @return int
     */
    public static function floor($value): int
    {
        return (int)floor((float)$value);
    }

    /**
     * @param $value
     * @return int
     */
    public static function ceil($value): int
    {
        return (int)ceil((float)$value);
    }

    /**
     * @param $value
     * @return int
     */
    public static function abs($value): int
    {
        return (int)abs($value);
    }

    /**
     * @param $value
     * @param int $precision
     * @param int $mode
     * @return float
     */
    public static function round($value, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): float
    {
        return (float)round((float)$value, $precision, $mode);
    }

    /**
     * @param $value
     * @return int
     */
    public static function roundInt($value): int
    {
        return (int)round((float)$value);
    }
}
