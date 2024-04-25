<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use ArrayAccess;
use Mini\Support\Arr;
use Mini\Support\Traits\Macroable;
use LogicException;
use Psr\Http\Message\RequestInterface;

class Request implements ArrayAccess
{
    use Macroable;

    /**
     * The underlying PSR request.
     *
     * @var null|RequestInterface
     */
    protected ?RequestInterface $request;

    /**
     * The decoded payload for the request.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Create a new request instance.
     *
     * @param RequestInterface $request
     * @return void
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->request->getMethod();
    }

    /**
     * Get the URL of the request.
     *
     * @return string
     */
    public function url(): string
    {
        return (string)$this->request->getUri();
    }

    /**
     * Determine if the request has a given header.
     *
     * @param string $key
     * @param mixed|null $value
     * @return bool
     */
    public function hasHeader(string $key, mixed $value = null): bool
    {
        if (is_null($value)) {
            return !empty($this->request->getHeaders()[$key]);
        }

        $headers = $this->headers();

        if (!Arr::has($headers, $key)) {
            return false;
        }

        $value = is_array($value) ? $value : [$value];

        return empty(array_diff($value, $headers[$key]));
    }

    /**
     * Determine if the request has the given headers.
     *
     * @param array|string $headers
     * @return bool
     */
    public function hasHeaders(array|string $headers): bool
    {
        if (is_string($headers)) {
            $headers = [$headers => null];
        }

        foreach ($headers as $key => $value) {
            if (!$this->hasHeader($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the values for the header with the given name.
     *
     * @param string $key
     * @return array
     */
    public function header(string $key): array
    {
        return Arr::get($this->headers(), $key, []);
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * Get the body of the request.
     *
     * @return string
     */
    public function body(): string
    {
        return (string)$this->request->getBody();
    }

    /**
     * Determine if the request contains the given file.
     *
     * @param string $name
     * @param string|null $value
     * @param string|null $filename
     * @return bool
     */
    public function hasFile(string $name, string $value = null, string $filename = null)
    {
        if (!$this->isMultipart()) {
            return false;
        }

        return collect($this->data)->reject(function ($file) use ($name, $value, $filename) {
                return $file['name'] !== $name ||
                    ($value && $file['contents'] !== $value) ||
                    ($filename && $file['filename'] !== $filename);
            })->count() > 0;
    }

    /**
     * Get the request's data (form parameters or JSON).
     *
     * @return array
     */
    public function data(): array
    {
        if ($this->isForm()) {
            return $this->parameters();
        }

        if ($this->isJson()) {
            return $this->json();
        }

        return $this->data ?? [];
    }

    /**
     * Get the request's form parameters.
     *
     * @return array
     */
    protected function parameters(): array
    {
        if (!$this->data) {
            parse_str($this->body(), $parameters);

            $this->data = $parameters;
        }

        return $this->data;
    }

    /**
     * Get the JSON decoded body of the request.
     *
     * @return array
     */
    protected function json(): array
    {
        if (!$this->data) {
            $this->data = json_decode($this->body(), true) ?? [];
        }

        return $this->data;
    }

    /**
     * Determine if the request is simple form data.
     *
     * @return bool
     */
    public function isForm(): bool
    {
        return $this->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Determine if the request is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->hasHeader('Content-Type') &&
            str_contains($this->header('Content-Type')[0], 'json');
    }

    /**
     * Determine if the request is multipart.
     *
     * @return bool
     */
    public function isMultipart(): bool
    {
        return $this->hasHeader('Content-Type') &&
            str_contains($this->header('Content-Type')[0], 'multipart');
    }

    /**
     * Set the decoded data on the request.
     *
     * @param array $data
     * @return $this
     */
    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the underlying PSR compliant request instance.
     *
     * @return RequestInterface|null
     */
    public function toPsrRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data()[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->data()[$offset];
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
        throw new LogicException('Request data may not be mutated using array access.');
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
        throw new LogicException('Request data may not be mutated using array access.');
    }
}
