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
 * Class UnauthorizedHttpException
 * @package Mini\Exception\HttpException
 */
class UnauthorizedHttpException extends HttpException
{
    /**
     * @param string $message The internal exception message
     * @param Exception|null $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct(string $message = '', Exception $previous = null, $code = 0)
    {
        parent::__construct($message, 401, [], $code, $previous);
    }
}