<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exceptions;

use Mini\Translate\Translate;
use RuntimeException;
use Throwable;

/**
 * Class HttpException
 * @package Mini\Exceptions
 */
class HttpException extends RuntimeException implements HttpExceptionInterface
{
    private array $headers;
    private $statusCode;
    private $responseMessage;

    /**
     * HttpException constructor.
     * @param int $statusCode
     * @param string|array $message
     * @param array $headers
     * @param int|null $code
     * @param Throwable|null $previous
     */
    public function __construct(int $statusCode, $message = '', array $headers = [], ?int $code = 0, Throwable $previous = null)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $this->responseMessage = $message ?: app(Translate::class)->getOrDefault('http_status_code.' . $statusCode, 'something error');
        parent::__construct('something error', $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getResponseMessage()
    {
        return $this->responseMessage;
    }
}