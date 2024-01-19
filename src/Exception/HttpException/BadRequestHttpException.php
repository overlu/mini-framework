<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception\HttpException;

use Exception;
use Mini\Exception\HttpException;

/**
 * Class BadRequestHttpException
 * @package Mini\Exception\HttpException
 */
class BadRequestHttpException extends HttpException
{
    /**
     * @param null $message The internal exception message
     * @param Exception|null $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct($message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct($message, 400, [], $code, $previous);
    }
}