<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

use Swoole\Http\Request;
use Swoole\WebSocket\Server;

interface WebsocketRequestInterface
{
    /**
     * Retrieve all input data from request, include query parameters, parsed body and json body.
     */
    public function all(): array;

    /**
     * Retrieve the data from query parameters, if $key is null, will return all query parameters.
     * @param string|null $key
     * @param mixed $default
     */
    public function query(?string $key = null, mixed $default = null): mixed;

    /**
     * Retrieve the data from parsed body, if $key is null, will return all parsed body.
     * @param string|null $key
     * @param mixed $default
     */
    public function post(?string $key = null, mixed $default = null): mixed;

    /**
     * Retrieve the input data from request, include query parameters, parsed body and json body.
     * @param string|null $key
     * @param mixed $default
     */
    public function input(?string $key = null, mixed $default = null): mixed;

    /**
     * Determine if the $key is exist in parameters.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @return int
     */
    public function getFd(): int;

    /**
     * @return Server
     */
    public function getServer(): Server;

    /**
     * @return Request
     */
    public function getSwooleRequest(): Request;
}
