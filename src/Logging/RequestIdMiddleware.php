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
     * @return mixed|void
     */
    public function before(string $method, string $className)
    {
        Seaslog::setRequestID(uniqid('mini.', true));
    }

    public function after(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('mini-request-id', Seaslog::getRequestID());
    }
}