<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Route;

use Exception;
use Mini\Context;
use Mini\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Mini\Service\HttpMessage\Uri\Uri;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class UrlGenerator implements UrlGeneratorContract
{
    /**
     * Get the full URL for the current request.
     *
     */
    public function full(): Uri
    {
        return request()->getUri();
    }

    /**
     * Get the current URL for the request.
     * @return Uri
     */
    public function current(): Uri
    {
        return request()->getUri()->withQuery('')->withFragment('');
    }

    /**
     * Get the path URL for the request.
     *
     * @param string $path
     * @return Uri
     */
    public function path(string $path = ''): Uri
    {
        return request()->getUri()->withQuery($path)->withFragment('');
    }

    /**
     * Get the URL for the previous request.
     *
     * @return Uri
     */
    public function previous(): Uri
    {
        return new Uri(request()->header('referer', ''));
    }

    /**
     * Determine if the given path is a valid URL.
     *
     * @param string $path
     * @return bool
     */
    public function isValidUrl(string $path): bool
    {
        if (!preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }

        return true;
    }

    /**
     * Generate a secure, absolute URL to the given path.
     *
     * @param string $path
     * @return Uri
     */
    public function secure(string $path = ''): Uri
    {
        return new Uri(str_ireplace('http://', 'https://', $path !== '' ? $path : (string)$this->full()));
    }

    /**
     * Generate a url
     *
     * @param string $path
     * @param array $params
     * @param string $fragment
     * @return Uri
     */
    public function make(string $path = '', array $params = [], string $fragment = ''): Uri
    {
        if (Context::has('IsInRequestEvent') && $request = request()) {
            $url = $request->getUri()->withQuery('')->withFragment('');
        } else {
            $url = new Uri(rtrim(env('APP_URL', 'http://localhost/')));
        }
        if ($path !== '') {
            $url = $url->withPath($path);
        }
        if (!empty($params)) {
            $url = $url->withQuerys($params);
        }
        if ($fragment !== '') {
            $url = $url->withFragment($fragment);
        }
        return $url;
    }

    /**
     * @param string|Uri $toUrl
     * @param int $status
     * @param string $schema
     * @return ResponseInterface
     * @throws Exception
     */
    public function redirect($toUrl, int $status = 302, string $schema = 'http'): ResponseInterface
    {
        return response()->redirect($toUrl, $status, $schema);
    }

    /**
     * @param $uri
     * @param string $scheme
     * @return Uri
     */
    public function withScheme($uri, string $scheme = ''): Uri
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        if (!$uri instanceof Uri) {
            throw new RuntimeException('Illegal uri');
        }

        return $uri->withScheme($scheme ?: request()->getScheme());
    }
}