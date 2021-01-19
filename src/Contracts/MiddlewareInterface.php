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
     * @return mixed
     */
    public function before();

    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    public function after(ResponseInterface $response);
}