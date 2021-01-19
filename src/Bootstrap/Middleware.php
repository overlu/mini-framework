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
        $this->middleware = $middleware;
    }

    /**
     * @return mixed|null
     */
    public function registerBeforeRequest()
    {
        foreach ($this->middleware as &$item) {
            if (!class_exists($item)) {
                throw new RuntimeException('class ' . $item . ' not exists.');
            }
            if ((($item = new $item) instanceof MiddlewareInterface) && !is_null($response = $item->before())) {
                return $response;
            }
        }
        return null;
    }

    public function bootAfterRequest(ResponseInterface $response)
    {
        foreach ($this->middleware as $item) {
            if ($item instanceof MiddlewareInterface) {
                $response = $item->after($response);
            }
        }
        return $response;
    }

    /**
     * add middleware
     * @param string $middleware
     */
    public function addMiddleware(string $middleware): void
    {
        if (!new $middleware instanceof MiddlewareInterface) {
            throw new RuntimeException($middleware . ' should instanceof ' . MiddlewareInterface::class);
        }
        $this->middleware[] = $middleware;
    }

    /**
     * remove middleware
     * @param string $middleware
     */
    public function removeMiddleware(string $middleware): void
    {
        if (isset($this->middleware[$middleware])) {
            unset($this->middleware[$middleware]);
        }
    }

    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return array
     */
    public function getBootedMiddleWares(): array
    {
        $bootedMiddleware = [];
        foreach ($this->middleware as $item) {
            if ($item instanceof MiddlewareInterface) {
                $bootedMiddleware[] = get_class($item);
            }
        }
        return $bootedMiddleware;
    }
}