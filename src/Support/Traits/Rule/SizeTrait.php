<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits\Rule;

use InvalidArgumentException;

trait SizeTrait
{
    /**
     * Get size value from given $value
     * @param $value
     * @return float|null
     */
    protected function getValueSize($value): ?float
    {
        if (is_numeric($value)) {
            $this->setKey($this->getKey() . '.numeric');
            return (float)$value;
        }
        if (is_string($value)) {
            $this->setKey($this->getKey() . '.string');
            return (float)mb_strlen($value, 'UTF-8');
        }
        if ($this->isUploadedFileValue($value)) {
            $this->setKey($this->getKey() . '.file');
            return (float)$value['size'];
        }
        if (is_array($value)) {
            $this->setKey($this->getKey() . '.array');
            return (float)count($value);
        }
        return null;
    }

    /**
     * Given $size and get the bytes
     * @param string|int $size
     * @return float
     * @throws InvalidArgumentException
     */
    protected function getBytesSize($size): float
    {
        if (is_numeric($size)) {
            return (float)$size;
        }

        if (!is_string($size)) {
            throw new InvalidArgumentException("Size must be string or numeric Bytes", 1);
        }

        if (!preg_match("/^(?<number>((\d+)?\.)?\d+)(?<format>(B|K|M|G|T|P)B?)?$/i", $size, $match)) {
            throw new InvalidArgumentException("Size is not valid format", 1);
        }

        $number = (float)$match['number'];
        $format = $match['format'] ?? '';

        switch (strtoupper($format)) {
            case "KB":
            case "K":
                return $number * 1024;
            case "MB":
            case "M":
                return $number * (1024 ** 2);
            case "GB":
            case "G":
                return $number * (1024 ** 3);
            case "TB":
            case "T":
                return $number * (1024 ** 4);
            case "PB":
            case "P":
                return $number * (1024 ** 5);
            default:
                return $number;
        }
    }

    /**
     * Check whether value is from $_FILES
     * @param mixed $value
     * @return bool
     */
    public function isUploadedFileValue($value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        $keys = ['name', 'type', 'tmp_name', 'size', 'error'];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $value)) {
                return false;
            }
        }
        return true;
    }
}
