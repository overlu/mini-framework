<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini;

use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\ResponseInterface;
use Mini\Service\HttpServer\Request;
use Mini\Service\HttpServer\Response;
class ConfigProvider
{
    public static function _invoke(): array
    {
        return [
            RequestInterface::class => Request::class,
            ResponseInterface::class => Response::class
        ];
    }
}