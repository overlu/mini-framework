<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\View\Middleware;

use Mini\Contracts\Middleware\MiddlewareInterface;
use Mini\Contracts\View\Factory as ViewFactory;
use Mini\Support\ViewErrorBag;
use Psr\Http\Message\ResponseInterface;

class ShareErrorsFromSession implements MiddlewareInterface
{
    /**
     * @param string $method
     * @param string $className
     * @return mixed
     */
    public function before(string $method, string $className)
    {
        return null;
    }

    /**
     * @param ResponseInterface $response
     * @param string $className
     * @return ResponseInterface
     */
    public function after(ResponseInterface $response, string $className): ResponseInterface
    {
        view()->share(
            'errors', request()->session()->get('errors') ?: new ViewErrorBag
        );

        return $response;
    }
}
