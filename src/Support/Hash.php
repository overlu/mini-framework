<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

class Hash
{
    /**
     * 从一个明文值生产哈希
     * @param $value
     * @param int $cost
     * @return false|string|null
     */
    public static function make($value, int $cost = 10)
    {
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    /**
     * 校验明文值与哈希是否匹配
     * @param string $value
     * @param string $hashValue
     * @return bool
     */
    public static function verify(string $value, string $hashValue): bool
    {
        return password_verify($value, $hashValue);
    }
}