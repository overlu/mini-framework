<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Bootstrap;

use Mini\Contracts\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Middleware
{
    private array $middleware = [];

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
     * @param string|null $method
     * @param object|null $class
     * @return mixed|null
     */
    public function registerBeforeRequest(?string $method = null, ?object $class = null): mixed
    {
        foreach ($this->middleware as $item) {
            if ($class && method_exists($class, 'disableMiddleware') && $class->disableMiddleware(get_class($item), 'before')) {
                continue;
            }
            if (!is_null($response = $item->before($method ?: '', $class ? get_class($class) : ''))) {
                return $response;
            }
        }
        return null;
    }

    /**
     * @param ResponseInterface $response
     * @param object|null $class
     * @return ResponseInterface
     */
    public function bootAfterRequest(ResponseInterface $response, ?object $class = null): ResponseInterface
    {
        $middleware = array_reverse($this->middleware);
        foreach ($middleware as $item) {
            if ($class && method_exists($class, 'disableMiddleware') && $class->disableMiddleware(get_class($item), 'after')) {
                continue;
            }
            $response = $item->after($response, $class ? get_class($class) : '');
        }
        return $response;
    }
}
