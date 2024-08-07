<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Mini\Facades\Lang;
use Mini\Facades\Request;
use RuntimeException;
use Throwable;

/**
 * Class HttpException
 * @package Mini\Exception
 */
class HttpException extends RuntimeException implements HttpExceptionInterface
{
    private array $headers;
    private mixed $statusCode;
    private mixed $responseMessage;

    /**
     * HttpException constructor.
     * @param mixed $statusCode
     * @param mixed $message
     * @param array $headers
     * @param int|null $code
     * @param Throwable|null $previous
     */
    public function __construct(mixed $message = '', $statusCode = 0, array $headers = [], ?int $code = 0, Throwable $previous = null)
    {
        if (config('routes.cors.enable')) {
            $headers += [
                'Access-Control-Allow-Origin' => Request::header('origin', '*'),
                'Access-Control-Allow-Methods' => config('routes.cors.access-control_allow_methods', '*'),
                'Access-Control-Allow-Headers' => '*',
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 1728000
            ];
        }
        $this->headers = $headers;
        $this->statusCode = Lang::has('http_status_code.' . $statusCode) ? $statusCode : 200;
        $this->responseMessage = $message ?: Lang::getOrDefault('http_status_code.' . $statusCode, 'something error');
        parent::__construct('something error', $statusCode, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
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
     * @return string|array
     */
    public function getResponseMessage(): mixed
    {
        return $this->responseMessage;
    }
}