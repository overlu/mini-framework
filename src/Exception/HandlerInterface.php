<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Exception;

use Mini\Contracts\HttpMessage\RequestInterface;
use Mini\Contracts\HttpMessage\WebsocketRequestInterface;
use Throwable;

/**
 * Interface HandlerInterface
 * @package Mini\Exception
 */
interface HandlerInterface
{
    public function report(Throwable $throwable);

    /**
     * @param RequestInterface|WebsocketRequestInterface $request
     * @param Throwable $throwable
     * @return void
     */
    public function render(RequestInterface|WebsocketRequestInterface $request, Throwable $throwable): void;
}