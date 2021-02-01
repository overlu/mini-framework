<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception\HttpException;

use Mini\Exception\HttpException;

/**
 * Class NotAcceptableHttpException
 * @package Mini\Exception\HttpException
 */
class NotAcceptableHttpException extends HttpException
{
    /**
     * @param string $message The internal exception message
     * @param \Exception $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct($message, 406, [], $code, $previous);
    }
}