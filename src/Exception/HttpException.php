<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Mini\Translate\Translate;
use RuntimeException;
use Throwable;

/**
 * Class HttpException
 * @package Mini\Exception
 */
class HttpException extends RuntimeException implements HttpExceptionInterface
{
    private array $headers;
    private $statusCode;
    private $responseMessage;

    /**
     * HttpException constructor.
     * @param mixed $statusCode
     * @param string|array $message
     * @param array $headers
     * @param int|null $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $statusCode = 0, array $headers = [], ?int $code = 0, Throwable $previous = null)
    {
        $this->headers = $headers;
        $this->statusCode = app(Translate::class)->has('http_status_code.' . $statusCode) ? $statusCode : 200;
        $this->responseMessage = $message ?: app(Translate::class)->getOrDefault('http_status_code.' . $statusCode, 'something error');
        parent::__construct('something error', $statusCode, $previous);
    }

    /**
     * @return int|mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array|string
     */
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }
}