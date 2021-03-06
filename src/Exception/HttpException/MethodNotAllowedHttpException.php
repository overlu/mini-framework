<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception\HttpException;

use Mini\Exception\HttpException;

/**
 * Class MethodNotAllowedHttpException
 * @package Mini\Exception
 */
class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param array $allow An array of allowed methods
     * @param string $message The internal exception message
     * @param \Exception $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct(array $allow = [], $message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct($message, 405, empty($allow) ? [] : ['Allow' => strtoupper(implode(', ', $allow))], $code, $previous);
    }
}