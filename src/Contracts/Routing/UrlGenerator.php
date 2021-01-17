<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\Routing;

use Psr\Http\Message\UriInterface;

/**
 * Interface UrlGenerator
 * @package Mini\Contracts\Routing
 */
interface UrlGenerator
{
    /**
     * Get the full URL for the current request.
     *
     * @return UriInterface
     */
    public function full(): UriInterface;

    /**
     * Get the current URL for the request.
     *
     * @return UriInterface
     */
    public function current(): UriInterface;

    /**
     * Get the URL for the previous request.
     *
     * @return UriInterface
     */
    public function previous(): UriInterface;

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * @param string $path
     * @return UriInterface
     */
    public function secure(string $path = ''): UriInterface;

    /**
     * Get the current URL for the request.
     *
     * @param string $path
     * @return UriInterface
     */
    public function path(string $path = ''): UriInterface;
}