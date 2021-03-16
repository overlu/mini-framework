<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

class Random
{
    /**
     * @param int $length
     * @param string $alphabet
     * @return false|string
     */
    public static function character($length = 6, $alphabet = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789')
    {
        mt_srand();
        if ($length >= strlen($alphabet)) {
            $rate = (int)($length / strlen($alphabet)) + 1;
            $alphabet = str_repeat($alphabet, $rate);
        }
        return substr(str_shuffle($alphabet), 0, $length);
    }

    /**
     * @param int $length
     * @return false|string
     */
    public static function number($length = 6)
    {
        return static::character($length, '0123456789');
    }

    /**
     * @param array $data
     * @return mixed
     */
    public static function arrayRandOne(array $data)
    {
        mt_srand();
        return $data[array_rand($data)];
    }
}