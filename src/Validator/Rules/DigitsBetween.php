<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exception\MissingRequiredParameterException;
use Mini\Validator\Rule;

class DigitsBetween extends Rule
{

    /** @var string */
    protected string $message = "The :attribute must have a length between the given :min and :max";

    /** @var array */
    protected array $fillableParams = ['min', 'max'];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);

        $min = (int)$this->parameter('min');
        $max = (int)$this->parameter('max');

        $length = strlen((string)$value);

        return !preg_match('/\D/', $value)
            && $length >= $min && $length <= $max;
    }
}
