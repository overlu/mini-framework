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
     * @param int|string $size
     * @return float
     * @throws InvalidArgumentException
     */
    protected function getBytesSize(int|string $size): float
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

        return match (strtoupper($format)) {
            "KB", "K" => $number * 1024,
            "MB", "M" => $number * (1024 ** 2),
            "GB", "G" => $number * (1024 ** 3),
            "TB", "T" => $number * (1024 ** 4),
            "PB", "P" => $number * (1024 ** 5),
            default => $number,
        };
    }

    /**
     * Check whether value is from $_FILES
     * @param mixed $value
     * @return bool
     */
    public function isUploadedFileValue(mixed $value): bool
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
