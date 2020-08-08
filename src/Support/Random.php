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
     * 生成随机字符串 可用于生成随机密码等
     * @param int $length 生成长度
     * @param string $alphabet 自定义生成字符集
     * @return bool|string
     * @author : evalor <master@evalor.cn>
     */
    public static function character($length = 6, $alphabet = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789')
    {
        mt_srand();
        // 重复字母表以防止生成长度溢出字母表长度
        if ($length >= strlen($alphabet)) {
            $rate = (int)($length / strlen($alphabet)) + 1;
            $alphabet = str_repeat($alphabet, $rate);
        }
        // 打乱顺序返回
        return substr(str_shuffle($alphabet), 0, $length);
    }

    /**
     * 生成随机数字 可用于生成随机验证码等
     * @param int $length 生成长度
     * @return bool|string
     * @author : evalor <master@evalor.cn>
     */
    public static function number($length = 6)
    {
        return static::character($length, '0123456789');
    }

    public static function arrayRandOne(array $data)
    {
        mt_srand();
        return $data[array_rand($data)];
    }
}