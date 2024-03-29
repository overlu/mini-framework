<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support;

use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use InvalidArgumentException;

class Json
{
    /**
     * @param $data
     * @param int $options
     * @return string
     */
    public static function encode($data, int $options = JSON_UNESCAPED_UNICODE): string
    {
        if ($data instanceof Jsonable) {
            return $data->toJson();
        }
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }
        $json = json_encode($data, $options);
        static::handleJsonError(json_last_error(), json_last_error_msg());
        return $json;
    }

    /**
     * @param string $json
     * @param bool $assoc
     * @return mixed
     */
    public static function decode(string $json, bool $assoc = true): mixed
    {
        $decode = json_decode($json, $assoc);
        static::handleJsonError(json_last_error(), json_last_error_msg());
        return $decode;
    }

    /**
     * @param $data
     * @return string
     */
    public static function pretty($data): string
    {
        return static::encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * @param $lastError
     * @param $message
     */
    protected static function handleJsonError($lastError, $message): void
    {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }
        throw new InvalidArgumentException($message, $lastError);
    }

    /**
     * @param string $string
     * @return bool
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}
