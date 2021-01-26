<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

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
     * @return mixed
     */
    public function after(ResponseInterface $response): ResponseInterface;
}