<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Logging;

use Mini\Contracts\ServiceRequestInterface;
use \Seaslog;
use Swoole\Http\Request;
use Swoole\Http\Response;

class LoggingRequestProvider implements ServiceRequestInterface
{
    public function before()
    {
        Seaslog::setRequestID(uniqid('', true));
    }

    public function after($response)
    {
        return $response->withHeader('mini-request-id', Seaslog::getRequestID());
    }
}