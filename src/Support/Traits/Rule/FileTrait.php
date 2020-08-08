<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits\Rule;

use Mini\Validator\Helper;

trait FileTrait
{

    /**
     * Check whether value is from $_FILES
     * @param mixed $value
     * @return bool
     */
    public function isValueFromUploadedFiles($value): bool
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

    /**
     * Check the $value is uploaded file
     * @param mixed $value
     * @return bool
     */
    public function isUploadedFile($value): bool
    {
        return $this->isValueFromUploadedFiles($value) && is_uploaded_file($value['tmp_name']);
    }

    /**
     * Resolve uploaded file value
     * @param mixed $value
     * @return array|null
     */
    public function resolveUploadedFileValue($value): ?array
    {
        if (!$this->isValueFromUploadedFiles($value)) {
            return null;
        }

        $arrayDots = Helper::arrayDot($value);

        $results = [];
        foreach ($arrayDots as $key => $val) {
            $splits = explode(".", $key);
            $firstKey = array_shift($splits);
            $key = count($splits) ? implode(".", $splits) . ".{$firstKey}" : $firstKey;

            Helper::arraySet($results, $key, $val);
        }
        return $results;
    }
}
