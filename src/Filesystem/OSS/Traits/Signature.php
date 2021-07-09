<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Filesystem\OSS\Traits;

/**
 * Class Signature
 * @package Mini\Filesystem\OSS\Traits
 */
trait Signature
{
    /**
     * oss 直传配置.
     * @param string $prefix
     * @param string|null $callBackUrl
     * @param array $customData
     * @param int $expire
     * @param int $contentLengthRangeValue
     * @param array $systemData
     * @return array
     * @throws \Exception
     */
    public function getOssSignatureConfig(string $prefix = '', ?string $callBackUrl = null, array $customData = [], int $expire = 30, int $contentLengthRangeValue = 1048576000, array $systemData = []): array
    {
        if (!empty($prefix)) {
            $prefix = ltrim($prefix, '/');
        }

        // 系统参数
        $system = [];
        if (empty($systemData)) {
            $system = self::SYSTEM_FIELD;
        } else {
            foreach ($systemData as $key => $value) {
                if (!in_array($value, self::SYSTEM_FIELD, true)) {
                    throw new \InvalidArgumentException("Invalid oss system filed: ${value}");
                }
                $system[$key] = $value;
            }
        }

        // 自定义参数
        $callbackVar = [];
        $data = [];
        if (!empty($customData)) {
            foreach ($customData as $key => $value) {
                $callbackVar['x:' . $key] = $value;
                $data[$key] = '${x:' . $key . '}';
            }
        }

        $callbackParam = [
            'callbackUrl' => $callBackUrl,
            'callbackBody' => urldecode(http_build_query(array_merge($system, $data))),
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];
        $callbackString = json_encode($callbackParam, JSON_THROW_ON_ERROR);
        $base64CallbackBody = base64_encode($callbackString);

        $end = time() + $expire;
        $expiration = $this->gmt_iso8601($end);

        // 最大文件大小.用户可以自己设置
        $conditions = [
            [
                0 => 'content-length-range',
                1 => 0,
                2 => $contentLengthRangeValue,
            ],
            [
                0 => 'starts-with',
                1 => '$key',
                2 => $prefix,
            ]
        ];

        $base64Policy = base64_encode(json_encode([
            'expiration' => $expiration,
            'conditions' => $conditions,
        ], JSON_THROW_ON_ERROR));

        return [
            'access_id' => $this->accessKeyId,
            'host' => $this->normalizeHost(),
            'policy' => $base64Policy,
            'signature' => base64_encode(hash_hmac('sha1', $base64Policy, $this->accessKeySecret, true)),
            'expire' => $end,
            'callback' => $base64CallbackBody,
            'callback_var' => $callbackVar,
            'dir' => $prefix    // 这个参数是设置用户上传文件时指定的前缀。
        ];
    }

    /**
     * gmt.
     * @param $time
     * @return string
     * @throws \Exception
     */
    public function gmt_iso8601($time): string
    {
        // fix bug https://connect.console.aliyun.com/connect/detail/162632
        return (new \DateTime('now', new \DateTimeZone('UTC')))->setTimestamp($time)->format('Y-m-d\TH:i:s\Z');
    }
}
