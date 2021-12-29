<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use Mini\Contracts\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use \Seaslog;

class RequestIdMiddleware implements MiddlewareInterface
{
    /**
     * @param string $method
     * @param string $className
     * @return void
     */
    public function before(string $method, string $className)
    {
        Seaslog::setRequestID(uniqid('mini.', true));
    }

    /**
     * @param ResponseInterface $response
     * @param string $className
     * @return ResponseInterface
     */
    public function after(ResponseInterface $response, string $className): ResponseInterface
    {
        return $response->withHeader('mini-request-id', Seaslog::getRequestID());
    }
}