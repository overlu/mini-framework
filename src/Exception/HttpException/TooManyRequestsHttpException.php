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
 * Class TooManyRequestsHttpException
 * @package Mini\Exception
 */
class TooManyRequestsHttpException extends HttpException
{
    /**
     * @param null $retryAfter The number of seconds or HTTP-date after which the request may be retried
     * @param string $message The internal exception message
     * @param Exception|null $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct($retryAfter = null, string $message = '', Exception $previous = null, $code = 0)
    {
        parent::__construct($message, 429, $retryAfter ? ['Retry-After' => $retryAfter] : [], $code, $previous);
    }

}