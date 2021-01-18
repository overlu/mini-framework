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
    public function before()
    {
        Seaslog::setRequestID(uniqid('', true));
    }

    public function after(ResponseInterface $response)
    {
        return $response->withHeader('mini-request-id', Seaslog::getRequestID());
    }
}