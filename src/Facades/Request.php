<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class Request
 * @package Mini\Facades
 */
class Request extends Facade
{
    protected static function getFacadeAccessor()
    {
        if (!Context::has('IsInRequestEvent')) {
            throw new RuntimeException("Not In Request Environment.");
        }
        return \Mini\Contracts\HttpMessage\RequestInterface::class;
    }
}