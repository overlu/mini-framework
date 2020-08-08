<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Validator\Rule;

class Present extends Rule
{
    /** @var bool */
    protected bool $implicit = true;

    /** @var string */
    protected string $message = "The :attribute must be present";

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     */
    public function check($value): bool
    {
        return $this->validation->hasValue($this->attribute->getKey());
    }
}
