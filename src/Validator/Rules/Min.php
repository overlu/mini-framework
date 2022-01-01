<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exception\MissingRequiredParameterException;
use Mini\Support\Traits\Rule\SizeTrait;
use Mini\Validator\Rule;

class Min extends Rule
{
    use SizeTrait;

    /** @var string */
    protected string $message = "The :attribute minimum is :min";

    /** @var array */
    protected array $fillableParams = ['min'];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        if (is_null($value)) {
            return false;
        }
        $this->requireParameters($this->fillableParams);

        $min = $this->getBytesSize($this->parameter('min'));
        $valueSize = $this->getValueSize($value);

        if (!is_numeric($valueSize)) {
            return false;
        }

        return $valueSize >= $min;
    }
}
