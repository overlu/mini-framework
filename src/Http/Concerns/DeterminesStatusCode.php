<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http\Concerns;

trait DeterminesStatusCode
{
    /**
     * Determine if the response code was 200 "OK" response.
     *
     * @return bool
     */
    public function ok(): bool
    {
        return $this->status() === 200;
    }

    /**
     * Determine if the response code was 201 "Created" response.
     *
     * @return bool
     */
    public function created(): bool
    {
        return $this->status() === 201;
    }

    /**
     * Determine if the response code was 202 "Accepted" response.
     *
     * @return bool
     */
    public function accepted(): bool
    {
        return $this->status() === 202;
    }

    /**
     * Determine if the response code was the given status code and the body has no content.
     *
     * @param int $status
     * @return bool
     */
    public function noContent(int $status = 204): bool
    {
        return $this->status() === $status && $this->body() === '';
    }

    /**
     * Determine if the response code was a 301 "Moved Permanently".
     *
     * @return bool
     */
    public function movedPermanently(): bool
    {
        return $this->status() === 301;
    }

    /**
     * Determine if the response code was a 302 "Found" response.
     *
     * @return bool
     */
    public function found(): bool
    {
        return $this->status() === 302;
    }

    /**
     * Determine if the response code was a 304 "Not Modified" response.
     *
     * @return bool
     */
    public function notModified(): bool
    {
        return $this->status() === 304;
    }

    /**
     * Determine if the response was a 400 "Bad Request" response.
     *
     * @return bool
     */
    public function badRequest(): bool
    {
        return $this->status() === 400;
    }

    /**
     * Determine if the response was a 401 "Unauthorized" response.
     *
     * @return bool
     */
    public function unauthorized(): bool
    {
        return $this->status() === 401;
    }

    /**
     * Determine if the response was a 402 "Payment Required" response.
     *
     * @return bool
     */
    public function paymentRequired(): bool
    {
        return $this->status() === 402;
    }

    /**
     * Determine if the response was a 403 "Forbidden" response.
     *
     * @return bool
     */
    public function forbidden(): bool
    {
        return $this->status() === 403;
    }

    /**
     * Determine if the response was a 404 "Not Found" response.
     *
     * @return bool
     */
    public function notFound(): bool
    {
        return $this->status() === 404;
    }

    /**
     * Determine if the response was a 408 "Request Timeout" response.
     *
     * @return bool
     */
    public function requestTimeout(): bool
    {
        return $this->status() === 408;
    }

    /**
     * Determine if the response was a 409 "Conflict" response.
     *
     * @return bool
     */
    public function conflict(): bool
    {
        return $this->status() === 409;
    }

    /**
     * Determine if the response was a 422 "Unprocessable Entity" response.
     *
     * @return bool
     */
    public function unprocessableEntity(): bool
    {
        return $this->status() === 422;
    }

    /**
     * Determine if the response was a 429 "Too Many Requests" response.
     *
     * @return bool
     */
    public function tooManyRequests(): bool
    {
        return $this->status() === 429;
    }
}
