<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use Mini\Contracts\HttpMessage\ResponseInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

interface ServiceRequestInterface
{
    public function before();

    /**
     * @param Mini\Service\HttpServer\Response | Mini\Service\HttpMessage\Server\Response $response
     * @return mixed
     */
    public function after(\Psr\Http\Message\ResponseInterface $response);
}