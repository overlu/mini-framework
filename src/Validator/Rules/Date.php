<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exceptions\MissingRequiredParameterException;
use Mini\Validator\Rule;

class Date extends Rule
{

    /** @var string */
    protected string $message = "The :attribute is not valid date format";

    /** @var array */
    protected array $fillableParams = ['format'];

    /** @var array */
    protected array $params = [
        'format' => 'Y-m-d'
    ];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);

        $format = $this->parameter('format');
        return date_create_from_format($format, $value) !== false;
    }
}
