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
    public function before(Request $request, Response $response): void
    {
        Seaslog::setRequestID(uniqid('', true));
    }

    public function after(Request $request, Response $response): void
    {
        $response->setHeader('mini-request-id', Seaslog::getRequestID());
    }
}