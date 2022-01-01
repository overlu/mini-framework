<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Validator\Rule;

class Uppercase extends Rule
{

    /** @var string */
    protected string $message = "The :attribute must be uppercase";

    /**
     * Check the $value is valid
     *
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        return mb_strtoupper($value, mb_detect_encoding($value)) === $value;
    }
}
