<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem\OSS\Traits;

use Mini\Facades\Request;

/**
 * Class Verify
 * @package Mini\Filesystem\OSS\Traits
 */
trait Verify
{
    /**
     * @return array
     */
    public function verify(): array
    {
        // oss 前面header、公钥 header
        $authorizationBase64 = Request::server('HTTP_AUTHORIZATION', '');
        $pubKeyUrlBase64 = Request::server('HTTP_X_OSS_PUB_KEY_URL', '');

        // 验证失败
        if (!$authorizationBase64 || !$pubKeyUrlBase64) {
            return [false, ['CallbackFailed' => 'authorization or pubKeyUrl is null']];
        }

        // 获取OSS的签名
        $authorization = base64_decode($authorizationBase64);
        // 获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        // 请求验证
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);


        if (!$pubKey) {
            return [false, ['CallbackFailed' => 'curl is fail']];
        }

        // 获取回调 body
        $body = file_get_contents('php://input');
        // 拼接待签名字符串
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if (false === $pos) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }
        // 验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);

        if (1 !== $ok) {
            return [false, ['CallbackFailed' => 'verify is fail, Illegal data']];
        }

        parse_str($body, $data);

        return [true, $data];
    }
}
