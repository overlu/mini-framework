<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Validator\Rule;

class Ip extends Rule
{

    /** @var string */
    protected string $message = "The :attribute is not valid IP Address";

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
