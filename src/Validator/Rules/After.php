<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Exception;
use Mini\Exception\MissingRequiredParameterException;
use Mini\Support\Traits\Rule\DateUtilsTrait;
use Mini\Validator\Rule;

class After extends Rule
{

    use DateUtilsTrait;

    /** @var string */
    protected string $message = "The :attribute must be a date after :date.";

    /** @var array */
    protected array $fillableParams = ['date'];

    /**
     * Check the value is valid
     * @param mixed $value
     * @return bool
     * @throws MissingRequiredParameterException
     * @throws Exception
     */
    public function check($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $this->requireParameters($this->fillableParams);
        $time = $this->parameter('date');

        if (!$this->isValidDate($value)) {
            throw $this->throwException($value);
        }

        if (!$this->isValidDate($time)) {
            throw $this->throwException($time);
        }

        return $this->getTimeStamp($time) < $this->getTimeStamp($value);
    }
}
