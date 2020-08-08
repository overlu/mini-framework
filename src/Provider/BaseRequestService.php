<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Provider;

use Mini\Contracts\ServiceRequestInterface;
use Mini\Singleton;
use Swoole\Http\Request;
use Swoole\Http\Response;

class BaseRequestService
{
    use Singleton;

    private array $services;

    private function __construct()
    {
        $this->services = config('app.requests', []);
    }

    public function before(Request $request, Response $response): void
    {
        foreach ($this->services as $service) {
            $service = new $service;
            if ($service instanceof ServiceRequestInterface) {
                $service->before($request, $response);
            }
        }
    }

    public function after(Request $request, Response $response): void
    {
        foreach ($this->services as $service) {
            $service = new $service;
            if ($service instanceof ServiceRequestInterface) {
                $service->after($request, $response);
            }
        }
    }
}