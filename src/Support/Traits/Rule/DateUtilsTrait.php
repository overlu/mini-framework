<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Support\Traits\Rule;

use Exception;

trait DateUtilsTrait
{

    /**
     * Check the $date is valid
     * @param string $date
     * @return bool
     */
    protected function isValidDate(string $date): bool
    {
        return (strtotime($date) !== false);
    }

    /**
     * Throw exception
     * @param string $value
     * @return Exception
     */
    protected function throwException(string $value): Exception
    {
        return new Exception("Expected a valid date, got '{$value}' instead. 2016-12-08, 2016-12-02 14:58, tomorrow are considered valid dates");
    }

    /**
     * Given $date and get the time stamp
     * @param string $date
     * @return int
     */
    protected function getTimeStamp(string $date): int
    {
        return strtotime($date);
    }
}
