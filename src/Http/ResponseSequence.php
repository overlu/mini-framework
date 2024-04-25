<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Mini\Support\Traits\Macroable;
use OutOfBoundsException;

class ResponseSequence
{
    use Macroable;

    /**
     * The responses in the sequence.
     *
     * @var array
     */
    protected array $responses;

    /**
     * Indicates that invoking this sequence when it is empty should throw an exception.
     *
     * @var bool
     */
    protected bool $failWhenEmpty = true;

    /**
     * The response that should be returned when the sequence is empty.
     *
     * @var PromiseInterface
     */
    protected PromiseInterface $emptyResponse;

    /**
     * Create a new response sequence.
     *
     * @param array $responses
     * @return void
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * Push a response to the sequence.
     *
     * @param array|string|null $body
     * @param int $status
     * @param array $headers
     * @return $this
     */
    public function push(array|string $body = null, int $status = 200, array $headers = []): self
    {
        return $this->pushResponse(
            Factory::response($body, $status, $headers)
        );
    }

    /**
     * Push a response with the given status code to the sequence.
     *
     * @param int $status
     * @param array $headers
     * @return $this
     */
    public function pushStatus(int $status, array $headers = []): self
    {
        return $this->pushResponse(
            Factory::response('', $status, $headers)
        );
    }

    /**
     * Push response with the contents of a file as the body to the sequence.
     *
     * @param string $filePath
     * @param int $status
     * @param array $headers
     * @return $this
     */
    public function pushFile(string $filePath, int $status = 200, array $headers = []): self
    {
        $string = file_get_contents($filePath);

        return $this->pushResponse(
            Factory::response($string, $status, $headers)
        );
    }

    /**
     * Push a response to the sequence.
     *
     * @param mixed $response
     * @return $this
     */
    public function pushResponse(mixed $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Make the sequence return a default response when it is empty.
     *
     * @param Closure|PromiseInterface $response
     * @return $this
     */
    public function whenEmpty(PromiseInterface|Closure $response): self
    {
        $this->failWhenEmpty = false;
        $this->emptyResponse = $response;

        return $this;
    }

    /**
     * Make the sequence return a default response when it is empty.
     *
     * @return $this
     */
    public function dontFailWhenEmpty(): self
    {
        return $this->whenEmpty(Factory::response());
    }

    /**
     * Indicate that this sequence has depleted all of its responses.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->responses) === 0;
    }

    /**
     * Get the next response in the sequence.
     *
     * @return mixed
     *
     * @throws OutOfBoundsException
     */
    public function __invoke(): mixed
    {
        if ($this->failWhenEmpty && $this->isEmpty()) {
            throw new OutOfBoundsException('A request was made, but the response sequence is empty.');
        }

        if (!$this->failWhenEmpty && $this->isEmpty()) {
            return value($this->emptyResponse ?? Factory::response());
        }

        return array_shift($this->responses);
    }
}
