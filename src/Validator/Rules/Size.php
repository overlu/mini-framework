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

class Size extends Rule
{
    use SizeTrait;

    /** @var string */
    protected string $message = "The :attribute must be :size";

    /** @var array */
    protected array $fillableParams = ['size'];

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

        $size = $this->getBytesSize($this->parameter('size'));
        $valueSize = $this->getValueSize($value);

        if (!is_numeric($valueSize)) {
            return false;
        }

        return $valueSize === $size;
    }
}
