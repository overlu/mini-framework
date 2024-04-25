<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use ArrayAccess;
use Closure;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\TransferStats;
use Mini\Support\Collection;
use Mini\Support\Traits\Macroable;
use LogicException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Response implements ArrayAccess
{
    use Concerns\DeterminesStatusCode, Macroable {
        __call as macroCall;
    }

    /**
     * The underlying PSR response.
     *
     * @var null|ResponseInterface
     */
    protected ?ResponseInterface $response;

    /**
     * The decoded JSON response.
     *
     * @var array
     */
    protected array $decoded = [];

    /**
     * The request cookies.
     *
     * @var null|CookieJar
     */
    public ?CookieJar $cookies;

    /**
     * The transfer stats for the request.
     *
     * @var TransferStats|null
     */
    public ?TransferStats $transferStats;

    /**
     * Create a new response instance.
     *
     * @param MessageInterface $response
     * @return void
     */
    public function __construct(MessageInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function body(): string
    {
        return (string)$this->response->getBody();
    }

    /**
     * Get the JSON decoded body of the response as an array or scalar value.
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public function json(string $key = null, mixed $default = null): mixed
    {
        if (!$this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        return data_get($this->decoded, $key, $default);
    }

    /**
     * Get the JSON decoded body of the response as an object.
     *
     * @return object|null
     */
    public function object(): ?object
    {
        return json_decode($this->body(), false);
    }

    /**
     * Get the JSON decoded body of the response as a collection.
     *
     * @param string|null $key
     * @return Collection
     */
    public function collect(string $key = null): Collection
    {
        return Collection::make($this->json($key));
    }

    /**
     * Get a header from the response.
     *
     * @param string $header
     * @return string
     */
    public function header(string $header): string
    {
        return $this->response->getHeaderLine($header);
    }

    /**
     * Get the headers from the response.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get the status code of the response.
     *
     * @return int
     */
    public function status(): int
    {
        return (int)$this->response->getStatusCode();
    }

    /**
     * Get the reason phrase of the response.
     *
     * @return string
     */
    public function reason(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * Get the effective URI of the response.
     *
     * @return UriInterface|null
     */
    public function effectiveUri(): ?UriInterface
    {
        return $this->transferStats?->getEffectiveUri();
    }

    /**
     * Determine if the request was successful.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Determine if the response was a redirect.
     *
     * @return bool
     */
    public function redirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Determine if the response indicates a client error occurred.
     *
     * @return bool
     */
    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Determine if the response indicates a server error occurred.
     *
     * @return bool
     */
    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    /**
     * Execute the given callback if there was a server or client error.
     *
     * @param callable $callback
     * @return $this
     */
    public function onError(callable $callback): self
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Get the response cookies.
     *
     * @return CookieJar
     */
    public function cookies(): CookieJar
    {
        return $this->cookies;
    }

    /**
     * Get the handler stats of the response.
     *
     * @return array
     */
    public function handlerStats(): array
    {
        return $this->transferStats?->getHandlerStats() ?? [];
    }

    /**
     * Close the stream and any underlying resources.
     *
     * @return $this
     */
    public function close(): self
    {
        $this->response->getBody()->close();

        return $this;
    }

    /**
     * Get the underlying PSR response for the response.
     *
     * @return MessageInterface|ResponseInterface
     */
    public function toPsrResponse(): MessageInterface|ResponseInterface
    {
        return $this->response;
    }

    /**
     * Create an exception if a server or client error occurred.
     *
     * @return RequestException|null
     */
    public function toException(): ?RequestException
    {
        if ($this->failed()) {
            return new RequestException($this);
        }
        return null;
    }

    /**
     * Throw an exception if a server or client error occurred.
     *
     * @return $this
     */
    public function throw(): self
    {
        $callback = func_get_args()[0] ?? null;

        if ($this->failed()) {
            throw tap($this->toException(), function ($exception) use ($callback) {
                if ($callback && is_callable($callback)) {
                    $callback($this, $exception);
                }
            });
        }

        return $this;
    }

    /**
     * Throw an exception if a server or client error occurred and the given condition evaluates to true.
     *
     * @param bool|Closure $condition
     * @return $this
     *
     */
    public function throwIf(bool|Closure $condition): self
    {
        return value($condition, $this) ? $this->throw(func_get_args()[1] ?? null) : $this;
    }

    /**
     * Throw an exception if the response status code matches the given code.
     *
     * @param callable|int $statusCode
     * @return $this
     *
     * @throws RequestException
     */
    public function throwIfStatus(callable|int $statusCode): self
    {
        if (is_callable($statusCode) &&
            $statusCode($this->status(), $this)) {
            return $this->throw();
        }

        return $this->status() === $statusCode ? $this->throw() : $this;
    }

    /**
     * Throw an exception unless the response status code matches the given code.
     *
     * @param callable|int $statusCode
     * @return $this
     *
     * @throws RequestException
     */
    public function throwUnlessStatus(callable|int $statusCode): self
    {
        if (is_callable($statusCode)) {
            return $statusCode($this->status(), $this) ? $this : $this->throw();
        }

        return $this->status() === $statusCode ? $this : $this->throw();
    }

    /**
     * Throw an exception if the response status code is a 4xx level code.
     *
     * @return $this
     */
    public function throwIfClientError(): self
    {
        return $this->clientError() ? $this->throw() : $this;
    }

    /**
     * Throw an exception if the response status code is a 5xx level code.
     *
     * @return $this
     *
     */
    public function throwIfServerError(): self
    {
        return $this->serverError() ? $this->throw() : $this;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->json()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->json()[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param string $offset
     * @param mixed $value
     * @return void
     *
     * @throws LogicException
     */
    public function offsetSet($offset, mixed $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param string $offset
     * @return void
     *
     * @throws LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    }

    /**
     * Get the body of the response.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body();
    }

    /**
     * Dynamically proxy other methods to the underlying response.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return static::hasMacro($method)
            ? $this->macroCall($method, $parameters)
            : $this->response->{$method}(...$parameters);
    }
}
