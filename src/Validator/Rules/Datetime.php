<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exception\MissingRequiredParameterException;
use Mini\Validator\Rule;

class Datetime extends Rule
{

    /** @var string */
    protected string $message = "The :attribute is not valid datetime format";

    /** @var array */
    protected array $fillableParams = ['format'];

    /** @var array */
    protected array $params = [
        'format' => 'Y-m-d H:i:s'
    ];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $this->requireParameters($this->fillableParams);

        $format = $this->parameter('format');
        return date_create_from_format($format, $value) !== false;
    }
}
