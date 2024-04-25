<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use Closure;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\TransferStats;
use Mini\Events\Dispatcher;
use Mini\Support\Collection;
use Mini\Support\Str;
use Mini\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @mixin PendingRequest
 */
class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The event dispatcher implementation.
     *
     * @var Dispatcher|null
     */
    protected ?Dispatcher $dispatcher = null;

    /**
     * The middleware to apply to every request.
     *
     * @var array
     */
    protected array $globalMiddleware = [];

    /**
     * The stub callables that will handle requests.
     *
     * @var null|Collection
     */
    protected ?Collection $stubCallbacks;

    /**
     * Indicates if the factory is recording requests and responses.
     *
     * @var bool
     */
    protected bool $recording = false;

    /**
     * The recorded response array.
     *
     * @var array
     */
    protected array $recorded = [];

    /**
     * All created response sequences.
     *
     * @var array
     */
    protected array $responseSequences = [];

    /**
     * Indicates that an exception should be thrown if any request is not faked.
     *
     * @var bool
     */
    protected bool $preventStrayRequests = false;

    /**
     * Create a new factory instance.
     *
     * @param Dispatcher|null $dispatcher
     * @return void
     */
    public function __construct(Dispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;

        $this->stubCallbacks = collect();
    }

    /**
     * Add middleware to apply to every request.
     *
     * @param callable $middleware
     * @return $this
     */
    public function globalMiddleware(callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Add request middleware to apply to every request.
     *
     * @param callable $middleware
     * @return $this
     */
    public function globalRequestMiddleware(callable $middleware): self
    {
        $this->globalMiddleware[] = Middleware::mapRequest($middleware);

        return $this;
    }

    /**
     * Add response middleware to apply to every request.
     *
     * @param callable $middleware
     * @return $this
     */
    public function globalResponseMiddleware(callable $middleware): self
    {
        $this->globalMiddleware[] = Middleware::mapResponse($middleware);

        return $this;
    }

    /**
     * Create a new response instance for use during stubbing.
     *
     * @param array|string|null $body
     * @param int $status
     * @param array $headers
     * @return PromiseInterface
     */
    public static function response(array|string $body = null, int $status = 200, array $headers = []): PromiseInterface
    {
        if (is_array($body)) {
            $body = json_encode($body);

            $headers['Content-Type'] = 'application/json';
        }

        $response = new Psr7Response($status, $headers, $body);

        return Create::promiseFor($response);
    }

    /**
     * Get an invokable object that returns a sequence of responses in order for use during stubbing.
     *
     * @param array $responses
     * @return ResponseSequence
     */
    public function sequence(array $responses = []): ResponseSequence
    {
        return $this->responseSequences[] = new ResponseSequence($responses);
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param callable|array|null $callback
     * @return $this
     */
    public function fake(callable|array $callback = null): self
    {
        $this->record();

        $this->recorded = [];

        if (is_null($callback)) {
            $callback = function () {
                return static::response();
            };
        }

        if (is_array($callback)) {
            foreach ($callback as $url => $callable) {
                $this->stubUrl($url, $callable);
            }

            return $this;
        }

        $this->stubCallbacks = $this->stubCallbacks->merge(collect([
            function ($request, $options) use ($callback) {
                $response = $callback instanceof Closure
                    ? $callback($request, $options)
                    : $callback;

                if ($response instanceof PromiseInterface) {
                    $options['on_stats'](new TransferStats(
                        $request->toPsrRequest(),
                        $response->wait(),
                    ));
                }

                return $response;
            },
        ]));

        return $this;
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param string $url
     * @return ResponseSequence
     */
    public function fakeSequence(string $url = '*'): ResponseSequence
    {
        return tap($this->sequence(), function ($sequence) use ($url) {
            $this->fake([$url => $sequence]);
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param string $url
     * @param callable|PromiseInterface|Response $callback
     * @return $this
     */
    public function stubUrl(string $url, callable|PromiseInterface|Response $callback): self
    {
        return $this->fake(function ($request, $options) use ($url, $callback) {
            if (!Str::is(Str::start($url, '*'), $request->url())) {
                return null;
            }

            return $callback instanceof Closure || $callback instanceof ResponseSequence
                ? $callback($request, $options)
                : $callback;
        });
    }

    /**
     * Indicate that an exception should be thrown if any request is not faked.
     *
     * @param bool $prevent
     * @return $this
     */
    public function preventStrayRequests(bool $prevent = true): self
    {
        $this->preventStrayRequests = $prevent;

        return $this;
    }

    /**
     * Indicate that an exception should not be thrown if any request is not faked.
     *
     * @return $this
     */
    public function allowStrayRequests(): self
    {
        return $this->preventStrayRequests(false);
    }

    /**
     * Begin recording request / response pairs.
     *
     * @return $this
     */
    protected function record(): self
    {
        $this->recording = true;

        return $this;
    }

    /**
     * Record a request response pair.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function recordRequestResponsePair(Request $request, Response $response): void
    {
        if ($this->recording) {
            $this->recorded[] = [$request, $response];
        }
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     *
     * @param callable $callback
     * @return void
     */
    public function assertSent(callable $callback): void
    {
        PHPUnit::assertTrue(
            $this->recorded($callback)->count() > 0,
            'An expected request was not recorded.'
        );
    }

    /**
     * Assert that the given request was sent in the given order.
     *
     * @param array $callbacks
     * @return void
     */
    public function assertSentInOrder(array $callbacks): void
    {
        $this->assertSentCount(count($callbacks));

        foreach ($callbacks as $index => $url) {
            $callback = is_callable($url) ? $url : static function ($request) use ($url) {
                return $request->url() === $url;
            };

            PHPUnit::assertTrue($callback(
                $this->recorded[$index][0],
                $this->recorded[$index][1]
            ), 'An expected request (#' . ($index + 1) . ') was not recorded.');
        }
    }

    /**
     * Assert that a request / response pair was not recorded matching a given truth test.
     *
     * @param callable $callback
     * @return void
     */
    public function assertNotSent(callable $callback): void
    {
        PHPUnit::assertFalse(
            $this->recorded($callback)->count() > 0,
            'Unexpected request was recorded.'
        );
    }

    /**
     * Assert that no request / response pair was recorded.
     *
     * @return void
     */
    public function assertNothingSent(): void
    {
        PHPUnit::assertEmpty(
            $this->recorded,
            'Requests were recorded.'
        );
    }

    /**
     * Assert how many requests have been recorded.
     *
     * @param int $count
     * @return void
     */
    public function assertSentCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->recorded);
    }

    /**
     * Assert that every created response sequence is empty.
     *
     * @return void
     */
    public function assertSequencesAreEmpty(): void
    {
        foreach ($this->responseSequences as $responseSequence) {
            PHPUnit::assertTrue(
                $responseSequence->isEmpty(),
                'Not all response sequences are empty.'
            );
        }
    }

    /**
     * Get a collection of the request / response pairs matching the given truth test.
     *
     * @param callable|null $callback
     * @return Collection
     */
    public function recorded(callable $callback = null): Collection
    {
        if (empty($this->recorded)) {
            return collect();
        }

        $callback = $callback ?: static function () {
            return true;
        };

        return collect($this->recorded)->filter(function ($pair) use ($callback) {
            return $callback($pair[0], $pair[1]);
        });
    }

    /**
     * Create a new pending request instance for this factory.
     *
     * @return PendingRequest
     */
    protected function newPendingRequest(): PendingRequest
    {
        return new PendingRequest($this, $this->globalMiddleware);
    }

    /**
     * Get the current event dispatcher implementation.
     *
     * @return Dispatcher|null
     */
    public function getDispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }

    /**
     * Get the array of global middleware.
     *
     * @return array
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap($this->newPendingRequest(), function ($request) {
            $request->stub($this->stubCallbacks)->preventStrayRequests($this->preventStrayRequests);
        })->{$method}(...$parameters);
    }
}
