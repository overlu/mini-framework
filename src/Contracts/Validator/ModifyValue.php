<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Validator;

interface ModifyValue
{
    /**
     * Modify given value
     * so in current and next rules returned value will be used
     *
     * @param mixed $value
     * @return mixed
     */
    public function modifyValue(mixed $value): mixed;
}
