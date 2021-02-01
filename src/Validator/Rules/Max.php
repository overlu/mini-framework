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

class Max extends Rule
{
    use SizeTrait;

    /** @var string */
    protected string $message = "The :attribute maximum is :max";

    /** @var array */
    protected array $fillableParams = ['max'];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);

        $max = $this->getBytesSize($this->parameter('max'));
        $valueSize = $this->getValueSize($value);

        if (!is_numeric($valueSize)) {
            return false;
        }

        return $valueSize <= $max;
    }
}
