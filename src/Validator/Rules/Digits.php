<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exceptions\MissingRequiredParameterException;
use Mini\Validator\Rule;

class Digits extends Rule
{

    /** @var string */
    protected string $message = "The :attribute must be numeric and must have an exact length of :length";

    /** @var array */
    protected array $fillableParams = ['length'];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);

        $length = (int)$this->parameter('length');

        return !preg_match('/\D/', $value)
            && strlen((string)$value) === $length;
    }
}
