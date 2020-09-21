<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Provider;

use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Contracts\ServiceRequestInterface;
use Mini\Singleton;

class BaseRequestService
{
    use Singleton;

    private array $services;

    private function __construct()
    {
        $this->services = config('app.requests', []);
    }

    public function before()
    {
        foreach ($this->services as $service) {
            $service = new $service;
            if ($service instanceof ServiceRequestInterface) {
                $service->before();
            }
        }
    }

    public function after(\Psr\Http\Message\ResponseInterface $response)
    {
        foreach ($this->services as $service) {
            $service = new $service;
            if ($service instanceof ServiceRequestInterface) {
                $response = $service->after($response);
            }
        }
        return $response;
    }
}