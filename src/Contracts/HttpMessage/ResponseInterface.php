<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

use Mini\Service\HttpMessage\Cookie\Cookie;
use Mini\Contracts\Support\Arrayable;
use Mini\Contracts\Support\Jsonable;
use Mini\Contracts\Support\Xmlable;
use Mini\Service\HttpServer\Response;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Interface ResponseInterface
 * @package Mini\Contracts\HttpMessage
 * @mixin Response | \Mini\Service\HttpMessage\Server\Response
 */
interface ResponseInterface
{
    /**
     * Format data to JSON and return data with Content-Type:application/json header.
     *
     * @param array|Arrayable|Jsonable $data
     * @return PsrResponseInterface
     */
    public function json(Arrayable|array|Jsonable $data): PsrResponseInterface;

    /**
     * Format data to XML and return data with Content-Type:application/xml header.
     *
     * @param array|Arrayable|Xmlable $data
     * @param string $root the name of the root node
     * @return PsrResponseInterface
     */
    public function xml(Arrayable|Xmlable|array $data, string $root = 'root'): PsrResponseInterface;

    /**
     * Format data to a string and return data with Content-Type:text/plain header.
     * @param mixed $data
     * @return PsrResponseInterface
     */
    public function raw(mixed $data): PsrResponseInterface;

    /**
     * Redirect to a URL.
     * @param string $toUrl
     * @param int $status
     * @param string $schema
     * @return PsrResponseInterface
     */
    public function redirect(string $toUrl, int $status = 302, string $schema = 'http'): PsrResponseInterface;

    /**
     * Create a file download response.
     *
     * @param string $file the file path which want to send to client
     * @param string $name the alias name of the file that client receive
     * @return PsrResponseInterface
     */
    public function download(string $file, string $name = ''): PsrResponseInterface;

    /**
     * Override a response with a cookie.
     * @param Cookie $cookie
     * @return ResponseInterface
     */
    public function withCookie(Cookie $cookie): ResponseInterface;
}
