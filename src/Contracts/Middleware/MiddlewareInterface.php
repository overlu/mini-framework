<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Middleware;

use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * @param string $method
     * @param string $className
     * @return mixed
     */
    public function before(string $method, string $className);

    /**
     * @param ResponseInterface $response
     * @param string $className
     * @return ResponseInterface
     */
    public function after(ResponseInterface $response, string $className): ResponseInterface;
}