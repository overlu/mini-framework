<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Class HttpException
 * @package Mini\Exceptions
 */
class HttpException extends RuntimeException
{
    private array $headers;
    private int $statusCode;

    public function __construct(int $statusCode, string $message = '', Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $message = $message ?: __('http_status_code.' . $statusCode);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}