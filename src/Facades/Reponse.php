<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Facades;

/**
 * Class Reponse
 * @package Mini\Facades
 */
class Reponse extends Facade
{
    protected static function getFacadeAccessor()
    {
        if (!Context::has('IsInRequestEvent')) {
            throw new RuntimeException("Not In Request Environment.");
        }
        return \Mini\Contracts\HttpMessage\ResponseInterface::class;
    }
}