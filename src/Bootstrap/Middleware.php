<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Bootstrap;

use Mini\Contracts\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Middleware
{
    private array $middleware;

    public function __construct(array $middleware = [])
    {
        foreach ($middleware as $item) {
            if (!class_exists($item)) {
                throw new RuntimeException('class ' . $item . ' not exists.');
            }
            if (!($obj = new $item) instanceof MiddlewareInterface) {
                throw new RuntimeException('class ' . $item . ' should instanceof ' . MiddlewareInterface::class . '.');
            }
            $this->middleware[$item] = $obj;
        }
    }

    /**
     * @param string $method
     * @param string $className
     * @return mixed|null
     */
    public function registerBeforeRequest(string $method, string $className)
    {
        foreach ($this->middleware as $item) {
            if (!is_null($response = $item->before($method, $className))) {
                return $response;
            }
        }
        return null;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function bootAfterRequest(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->middleware as $item) {
            $response = $item->after($response);
        }
        return $response;
    }
}