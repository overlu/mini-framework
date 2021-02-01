<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Mini\Contracts\HttpMessage\RequestInterface;

/**
 * Interface HandlerInterface
 * @package Mini\Exception
 */
interface HandlerInterface
{
    public function report(\Throwable $throwable);

    public function render(RequestInterface $request, \Throwable $throwable);
}