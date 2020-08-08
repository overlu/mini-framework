<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface ServiceRequestInterface
{
    public function before(Request $request, Response $response): void;

    public function after(Request $request, Response $response): void;
}