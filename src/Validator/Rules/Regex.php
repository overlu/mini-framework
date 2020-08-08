<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exceptions\MissingRequiredParameterException;
use Mini\Validator\Rule;

class Regex extends Rule
{

    /** @var string */
    protected string $message = "The :attribute is not valid format";

    /** @var array */
    protected array $fillableParams = ['regex'];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);
        $regex = $this->parameter('regex');
        return preg_match($regex, $value) > 0;
    }
}
