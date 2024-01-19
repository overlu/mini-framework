<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use RuntimeException;

class MissingAppKeyException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     */
    public function __construct(string $message = 'No application encryption key has been specified.')
    {
        parent::__construct($message);
    }
}