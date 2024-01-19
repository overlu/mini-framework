<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Mini\Exception\MissingRequiredParameterException;
use Mini\Validator\Rule;
use Mini\Contracts\Validator\ModifyValue;

class Defaults extends Rule implements ModifyValue
{

    /** @var string */
    protected string $message = "The :attribute default is :default";

    /** @var array */
    protected array $fillableParams = ['default'];

    /**
     * Check the $value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);

        return (bool)$this->parameter('default');
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function modifyValue(mixed $value): mixed
    {
        return $this->isEmptyValue($value) ? $this->parameter('default') : $value;
    }

    /**
     * Check $value is empty value
     * @param mixed $value
     * @return boolean
     */
    protected function isEmptyValue(mixed $value): bool
    {
        return false === (new Required)->check($value);
    }
}
