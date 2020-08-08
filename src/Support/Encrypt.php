<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

class Encrypt
{

    /**
     * 生成加密数据
     * @return array
     */
    public static function make(): array
    {
        $nonce = self::getNonce();
        $timestamp = time();
        return [
            'nonce' => $nonce,
            'timestamp' => $timestamp,
            'signature' => self::sha1($nonce, $timestamp)
        ];
    }

    /**
     * @param string $nonce
     * @param string $signature
     * @param int $timestamp
     * @return bool
     */
    public static function verify(string $nonce, string $signature, int $timestamp): bool
    {
        return ((time() - $timestamp) < config('encrypt.signature_expiry') && self::sha1($nonce, $timestamp) === $signature && Signature::make($signature, $nonce));
    }

    /**
     * 生成随机数
     * @param int $length
     * @return string
     */
    public static function getNonce(int $length = 16): string
    {
        if (!is_int($length) || $length <= 0) {
            return '';
        }
        $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $char[random_int(0, strlen($char) - 1)];
        }
        return $nonce;
    }

    /**
     * 生成加密字符串
     * @param $nonce
     * @param $timestamp
     * @return string
     */
    private static function sha1(string $nonce, int $timestamp): string
    {
        $key = config('encrypt.key', '');
        $tempArr = [$nonce, $key, $timestamp];
        sort($tempArr, SORT_STRING);
        $tempStr = implode($tempArr);
        return sha1($tempStr);
    }
}
