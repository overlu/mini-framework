<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Mini\Service\HttpMessage\Upload\UploadedFile;
use Mini\Session\Session;
use Mini\Validator\Validation;
use Psr\Http\Message\ServerRequestInterface;

interface Request extends ServerRequestInterface
{
    /**
     * Retrieve all input data from request, include query parameters, parsed body and json body.
     */
    public function all(): array;

    /**
     * Retrieve the data from query parameters, if $key is null, will return all query parameters.
     * @param string|null $key
     * @param mixed|null $default
     */
    public function query(?string $key = null, mixed $default = null);

    /**
     * Retrieve the data from parsed body, if $key is null, will return all parsed body.
     * @param string|null $key
     * @param mixed $default
     */
    public function post(?string $key = null, mixed $default = null): mixed;

    /**
     * Retrieve the input data from request, include query parameters, parsed body and json body.
     * @param string $key
     * @param mixed $default
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Retrieve the input data from request via multi keys, include query parameters, parsed body and json body.
     * @param array $keys
     * @param mixed $default
     * @return array
     */
    public function inputs(array $keys, mixed $default = null): array;

    /**
     * Determine if the $keys is exist in parameters.
     * @param array $keys
     * @return array []array [found, not-found]
     */
    public function hasInput(array $keys): array;

    /**
     * Determine if the $keys is exist in parameters.
     *
     * @param array|string $keys
     * @return bool
     */
    public function has(array|string $keys): bool;

    /**
     * Retrieve the data from request headers.
     * @param string $key
     * @param mixed $default
     */
    public function header(string $key, mixed $default = null);

    /**
     * Returns the path being requested relative to the executed script.
     * The path info always starts with a /.
     * Suppose this request is instantiated from /mysite on localhost:
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'.
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo(): string;

    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param mixed ...$patterns
     * @return bool
     */
    public function is(...$patterns): bool;

    /**
     * Get the current decoded path info for the request.
     */
    public function decodedPath(): string;

    /**
     * Returns the requested URI (path and query string).
     *
     * @return string The raw URI (i.e. not URI decoded)
     */
    public function getRequestUri(): string;

    /**
     * Get the URL (no query string) for the request.
     */
    public function url(): string;

    /**
     * Get the full URL for the request.
     */
    public function fullUrl(): string;

    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return null|string A normalized query string for the Request
     */
    public function getQueryString(): ?string;

    /**
     * Normalizes a query string.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized,
     * have consistent escaping and unneeded delimiters are removed.
     *
     * @param string $qs Query string
     * @return string A normalized query string for the Request
     */
    public function normalizeQueryString(string $qs): string;

    /**
     * Retrieve a cookie from the request.
     * @param string $key
     * @param null|mixed $default
     */
    public function cookie(string $key, mixed $default = null): mixed;

    /**
     * Determine if a cookie is set on the request.
     * @param string $key
     * @return bool
     */
    public function hasCookie(string $key): bool;

    /**
     * Retrieve a server variable from the request.
     *
     * @param string $key
     * @param null|mixed $default
     * @return null|array|string
     */
    public function server(string $key, mixed $default = null): mixed;

    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     * @return bool
     */
    public function isMethod(string $method): bool;

    /**
     * Retrieve a file from the request.
     *
     * @param string $key
     * @param mixed|null $default
     * @return null|UploadedFile|UploadedFile[]
     */
    public function file(string $key, mixed $default = null): UploadedFile|array|null;

    /**
     * Determine if the uploaded data contains a file.
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool;

    /**
     * @param array $rules
     * @param array $messages
     * @param bool $bail
     * @return Validation
     */
    public function validate(array $rules, array $messages = [], bool $bail = true): Validation;

    /**
     * @return string
     */
    public function ip(): string;

    /**
     * @return bool
     */
    public function isSecure(): bool;

    /**
     * @return string
     */
    public function getScheme(): string;

    /**
     * @return array
     */
    public function getCookieParams(): array;

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys): array;

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path(): string;

    /**
     * @return Session
     */
    public function session(): Session;

    /**
     * @param string|null $key
     * @param $default
     * @return mixed
     */
    public function route(?string $key = null, $default = null): mixed;

    /**
     * @return array
     */
    public function headers(): array;

    /**
     * @return string
     */
    public function method(): string;

    /**
     * @return string
     */
    public function getClientIp(): string;

    /**
     * @return string[]
     */
    public function getClientIps(): array;

    /**
     * Retrieve input from the request as a String.
     *
     * @param string $key
     * @param string|null $default
     * @return string
     */
    public function str(string $key, string $default = null): string;

    /**
     * Retrieve input from the request as a String.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function string(string $key, string $default = ''): string;

    /**
     * Retrieve input as a boolean value.
     *
     * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function boolean(string $key, bool $default = false): bool;

    /**
     * Retrieve input as an integer value.
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    public function integer(string $key, int $default = 0): int;

    /**
     * Retrieve input as a float value.
     *
     * @param string $key
     * @param float $default
     * @return float
     */
    public function float(string $key, float $default = 0.0): float;

    /**
     * Retrieve input from the request as a Carbon instance.
     *
     * @param string $key
     * @param string|null $format
     * @param string|null $tz
     * @return Carbon|null
     *
     * @throws InvalidFormatException
     */
    public function date(string $key, string $format = null, string $tz = null): ?Carbon;
}
