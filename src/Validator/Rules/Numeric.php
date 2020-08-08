<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Validator\Rule;

class Numeric extends Rule
{

    /** @var string */
    protected string $message = "The :attribute must be numeric";

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        return is_numeric($value);
    }
}
