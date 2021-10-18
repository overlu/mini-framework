<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Mini\Contracts\Container\BindingResolutionException;
use Mini\Facades\Route;
use Mini\Translate\Translate;
use ReflectionException;
use RuntimeException;
use Throwable;

/**
 * Class HttpException
 * @package Mini\Exception
 */
class HttpException extends RuntimeException implements HttpExceptionInterface
{
    private array $headers;
    private int $statusCode;
    private $responseMessage;

    /**
     * HttpException constructor.
     * HttpException constructor.
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     * @param int|null $code
     * @param Throwable|null $previous
     * @throws BindingResolutionException
     * @throws Throwable
     * @throws ReflectionException
     */
    public function __construct($message = '', $statusCode = 0, array $headers = [], ?int $code = 0, Throwable $previous = null)
    {
        $this->headers = $headers;
        if ($this->hasStatusCode($statusCode)) {
            if ($handler = Route::route((string)$statusCode)) {
                Route::dispatchHandle($handler);
                return;
            }
            $this->statusCode = $statusCode;
        } else {
            $this->statusCode = 200;
        }
        $this->responseMessage = $message ?: app(Translate::class)->getOrDefault('http_status_code.' . $statusCode, 'something error');
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
     * @return array|string
     */
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }

    /**
     * @param $statusCode
     * @return bool
     * @throws BindingResolutionException
     */
    protected function hasStatusCode($statusCode): bool
    {
        return app(Translate::class)->has('http_status_code.' . $statusCode);
    }
}