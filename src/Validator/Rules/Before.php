<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Validator\Rules;

use Exception;
use Mini\Exceptions\MissingRequiredParameterException;
use Mini\Support\Traits\Rule\DateUtilsTrait;
use Mini\Validator\Rule;

class Before extends Rule
{
    use DateUtilsTrait;

    /** @var string */
    protected string $message = "The :attribute must be a date before :time.";

    /** @var array */
    protected array $fillableParams = ['time'];

    /**
     * Check the $value is valid
     * @param $value
     * @return bool
     * @throws MissingRequiredParameterException
     * @throws Exception
     */
    public function check($value): bool
    {
        $this->requireParameters($this->fillableParams);
        $time = $this->parameter('time');

        if (!$this->isValidDate($value)) {
            throw $this->throwException($value);
        }

        if (!$this->isValidDate($time)) {
            throw $this->throwException($time);
        }

        return $this->getTimeStamp($time) > $this->getTimeStamp($value);
    }
}
