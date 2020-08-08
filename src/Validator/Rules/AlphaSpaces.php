<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Validator\Rule;

class AlphaSpaces extends Rule
{

    /** @var string */
    protected string $message = "The :attribute may only allows alphabet and spaces";

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\s]+$/u', $value) > 0;
    }
}
