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
    public static function encode($data, $options = JSON_UNESCAPED_UNICODE): string
    {
        if ($data instanceof Jsonable) {
            return $data->toJson();
        }
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }
        $json = json_encode($data, JSON_THROW_ON_ERROR | $options);
        static::handleJsonError(json_last_error(), json_last_error_msg());
        return $json;
    }

    /**
     * @param string $json
     * @param bool $assoc
     * @return mixed
     */
    public static function decode(string $json, $assoc = true)
    {
        $decode = json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        static::handleJsonError(json_last_error(), json_last_error_msg());
        return $decode;
    }

    /**
     * @param $data
     * @return string
     */
    public static function pretty($data): string
    {
        return static::encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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
}
