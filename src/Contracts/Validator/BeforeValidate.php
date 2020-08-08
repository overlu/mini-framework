<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Validator;

interface BeforeValidate
{
    /**
     * Before validate hook
     *
     * @return void
     */
    public function beforeValidate();
}
