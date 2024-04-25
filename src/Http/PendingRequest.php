<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Http;

use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use GuzzleHttp\UriTemplate\UriTemplate;
use Mini\Contracts\Support\Arrayable;
use Mini\Http\Events\ConnectionFailed;
use Mini\Http\Events\RequestSending;
use Mini\Http\Events\ResponseReceived;
use Mini\Support\Arr;
use Mini\Support\Collection;
use Mini\Support\Str;
use Mini\Support\Stringable;
use Mini\Support\Traits\Conditionable;
use Mini\Support\Traits\Macroable;
use JsonSerializable;
use OutOfBoundsException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

class PendingRequest
{
    use Conditionable, Macroable;

    /**
     * The factory instance.
     *
     * @var Factory|null
     */
    protected $factory;

    /**
     * The Guzzle client instance.
     *
     * @var null|Client
     */
    protected ?Client $client;

    /**
     * The Guzzle HTTP handler.
     *
     * @var callable
     */
    protected $handler;

    /**
     * The base URL for the request.
     *
     * @var string
     */
    protected string $baseUrl = '';

    /**
     * The parameters that can be substituted into the URL.
     *
     * @var array
     */
    protected array $urlParameters = [];

    /**
     * The request body format.
     *
     * @var string
     */
    protected mixed $bodyFormat;

    /**
     * The raw body for the request.
     *
     * @var null|string
     */
    protected ?string $pendingBody = '';

    /**
     * The pending files for the request.
     *
     * @var array
     */
    protected array $pendingFiles = [];

    /**
     * The request cookies.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * The transfer stats for the request.
     *
     * @var null|TransferStats
     */
    protected ?TransferStats $transferStats;

    /**
     * The request options.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * A callback to run when throwing if a server or client error occurs.
     *
     * @var null|Closure
     */
    protected ?Closure $throwCallback;

    /**
     * A callback to check if an exception should be thrown when a server or client error occurs.
     *
     * @var null|Closure
     */
    protected ?Closure $throwIfCallback;

    /**
     * The number of times to try the request.
     *
     * @var int
     */
    protected int $tries = 1;

    /**
     * The number of milliseconds to wait between retries.
     *
     * @var Closure|int
     */
    protected Closure|int $retryDelay = 100;

    /**
     * Whether to throw an exception when all retries fail.
     *
     * @var bool
     */
    protected bool $retryThrow = true;

    /**
     * The callback that will determine if the request should be retried.
     *
     * @var callable|null
     */
    protected $retryWhenCallback = null;

    /**
     * The callbacks that should execute before the request is sent.
     *
     * @var null|Collection
     */
    protected ?Collection $beforeSendingCallbacks;

    /**
     * The stub callables that will handle requests.
     *
     * @var Collection|null
     */
    protected ?Collection $stubCallbacks;

    /**
     * Indicates that an exception should be thrown if any request is not faked.
     *
     * @var bool
     */
    protected bool $preventStrayRequests = false;

    /**
     * The middleware callables added by users that will handle requests.
     *
     * @var null|Collection
     */
    protected ?Collection $middleware;

    /**
     * Whether the requests should be asynchronous.
     *
     * @var bool
     */
    protected bool $async = false;

    /**
     * The pending request promise.
     *
     * @var PromiseInterface|null
     */
    protected ?PromiseInterface $promise;

    /**
     * The sent request object, if a request has been made.
     *
     * @var Request|null
     */
    protected ?Request $request;

    /**
     * The Guzzle request options that are mergable via array_merge_recursive.
     *
     * @var array
     */
    protected array $mergableOptions = [
        'cookies',
        'form_params',
        'headers',
        'json',
        'multipart',
        'query',
    ];

    /**
     * Create a new HTTP Client instance.
     *
     * @param Factory|null $factory
     * @param array $middleware
     * @return void
     */
    public function __construct(Factory $factory = null, array $middleware = [])
    {
        $this->factory = $factory;
        $this->middleware = new Collection($middleware);

        $this->asJson();

        $this->options = [
            'connect_timeout' => 10,
            'http_errors' => false,
            'timeout' => 30,
        ];

        $this->beforeSendingCallbacks = collect([function (Request $request, array $options, PendingRequest $pendingRequest) {
            $pendingRequest->request = $request;
            $pendingRequest->cookies = $options['cookies'];

            $pendingRequest->dispatchRequestSendingEvent();
        }]);
    }

    /**
     * Set the base URL for the pending request.
     *
     * @param string $url
     * @return $this
     */
    public function baseUrl(string $url): self
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Attach a raw body to the request.
     *
     * @param string $content
     * @param string $contentType
     * @return $this
     */
    public function withBody(string $content, string $contentType = 'application/json'): self
    {
        $this->bodyFormat('body');

        $this->pendingBody = $content;

        $this->contentType($contentType);

        return $this;
    }

    /**
     * Indicate the request contains JSON.
     *
     * @return $this
     */
    public function asJson(): self
    {
        return $this->bodyFormat('json')->contentType('application/json');
    }

    /**
     * Indicate the request contains form parameters.
     *
     * @return $this
     */
    public function asForm(): self
    {
        return $this->bodyFormat('form_params')->contentType('application/x-www-form-urlencoded');
    }

    /**
     * Attach a file to the request.
     *
     * @param array|string $name
     * @param string $contents
     * @param string|null $filename
     * @param array $headers
     * @return $this
     */
    public function attach(array|string $name, mixed $contents = '', string $filename = null, array $headers = []): self
    {
        if (is_array($name)) {
            foreach ($name as $file) {
                $this->attach(...$file);
            }

            return $this;
        }

        $this->asMultipart();

        $this->pendingFiles[] = array_filter([
            'name' => $name,
            'contents' => $contents,
            'headers' => $headers,
            'filename' => $filename,
        ]);

        return $this;
    }

    /**
     * Indicate the request is a multi-part form request.
     *
     * @return $this
     */
    public function asMultipart(): self
    {
        return $this->bodyFormat('multipart');
    }

    /**
     * Specify the body format of the request.
     *
     * @param string $format
     * @return $this
     */
    public function bodyFormat(string $format): self
    {
        return tap($this, function () use ($format) {
            $this->bodyFormat = $format;
        });
    }

    /**
     * Set the given query parameters in the request URI.
     *
     * @param array $parameters
     * @return $this
     */
    public function withQueryParameters(array $parameters): self
    {
        return tap($this, function () use ($parameters) {
            $this->options = array_merge_recursive($this->options, [
                'query' => $parameters,
            ]);
        });
    }

    /**
     * Specify the request's content type.
     *
     * @param string $contentType
     * @return $this
     */
    public function contentType(string $contentType): self
    {
        $this->options['headers']['Content-Type'] = $contentType;

        return $this;
    }

    /**
     * Indicate that JSON should be returned by the server.
     *
     * @return $this
     */
    public function acceptJson(): self
    {
        return $this->accept('application/json');
    }

    /**
     * Indicate the type of content that should be returned by the server.
     *
     * @param string $contentType
     * @return $this
     */
    public function accept(string $contentType): self
    {
        return $this->withHeaders(['Accept' => $contentType]);
    }

    /**
     * Add the given headers to the request.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        return tap($this, function () use ($headers) {
            $this->options = array_merge_recursive($this->options, [
                'headers' => $headers,
            ]);
        });
    }

    /**
     * Add the given header to the request.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function withHeader(string $name, mixed $value): self
    {
        return $this->withHeaders([$name => $value]);
    }

    /**
     * Replace the given headers on the request.
     *
     * @param array $headers
     * @return $this
     */
    public function replaceHeaders(array $headers): self
    {
        $this->options['headers'] = array_merge($this->options['headers'] ?? [], $headers);

        return $this;
    }

    /**
     * Specify the basic authentication username and password for the request.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withBasicAuth(string $username, string $password): self
    {
        return tap($this, function () use ($username, $password) {
            $this->options['auth'] = [$username, $password];
        });
    }

    /**
     * Specify the digest authentication username and password for the request.
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withDigestAuth(string $username, string $password): self
    {
        return tap($this, function () use ($username, $password) {
            $this->options['auth'] = [$username, $password, 'digest'];
        });
    }

    /**
     * Specify an authorization token for the request.
     *
     * @param string $token
     * @param string $type
     * @return $this
     */
    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return tap($this, function () use ($token, $type) {
            $this->options['headers']['Authorization'] = trim($type . ' ' . $token);
        });
    }

    /**
     * Specify the user agent for the request.
     *
     * @param bool|string $userAgent
     * @return $this
     */
    public function withUserAgent(bool|string $userAgent): self
    {
        return tap($this, function () use ($userAgent) {
            $this->options['headers']['User-Agent'] = trim($userAgent);
        });
    }

    /**
     * Specify the URL parameters that can be substituted into the request URL.
     *
     * @param array $parameters
     * @return $this
     */
    public function withUrlParameters(array $parameters = []): self
    {
        return tap($this, function () use ($parameters) {
            $this->urlParameters = $parameters;
        });
    }

    /**
     * Specify the cookies that should be included with the request.
     *
     * @param array $cookies
     * @param string $domain
     * @return $this
     */
    public function withCookies(array $cookies, string $domain): self
    {
        return tap($this, function () use ($cookies, $domain) {
            $this->options = array_merge_recursive($this->options, [
                'cookies' => CookieJar::fromArray($cookies, $domain),
            ]);
        });
    }

    /**
     * Specify the maximum number of redirects to allow.
     *
     * @param int $max
     * @return $this
     */
    public function maxRedirects(int $max): self
    {
        return tap($this, function () use ($max) {
            $this->options['allow_redirects']['max'] = $max;
        });
    }

    /**
     * Indicate that redirects should not be followed.
     *
     * @return $this
     */
    public function withoutRedirecting(): self
    {
        return tap($this, function () {
            $this->options['allow_redirects'] = false;
        });
    }

    /**
     * Indicate that TLS certificates should not be verified.
     *
     * @return $this
     */
    public function withoutVerifying(): self
    {
        return tap($this, function () {
            $this->options['verify'] = false;
        });
    }

    /**
     * Specify the path where the body of the response should be stored.
     *
     * @param string $to
     * @return $this
     */
    public function sink(string $to): self
    {
        return tap($this, function () use ($to) {
            $this->options['sink'] = $to;
        });
    }

    /**
     * Specify the timeout (in seconds) for the request.
     *
     * @param int $seconds
     * @return $this
     */
    public function timeout(int $seconds): self
    {
        return tap($this, function () use ($seconds) {
            $this->options['timeout'] = $seconds;
        });
    }

    /**
     * Specify the connect timeout (in seconds) for the request.
     *
     * @param int $seconds
     * @return $this
     */
    public function connectTimeout(int $seconds): self
    {
        return tap($this, function () use ($seconds) {
            $this->options['connect_timeout'] = $seconds;
        });
    }

    /**
     * Specify the number of times the request should be attempted.
     *
     * @param int $times
     * @param Closure|int $sleepMilliseconds
     * @param callable|null $when
     * @param bool $throw
     * @return $this
     */
    public function retry(int $times, Closure|int $sleepMilliseconds = 0, ?callable $when = null, bool $throw = true): self
    {
        $this->tries = $times;
        $this->retryDelay = $sleepMilliseconds;
        $this->retryThrow = $throw;
        $this->retryWhenCallback = $when;

        return $this;
    }

    /**
     * Replace the specified options on the request.
     *
     * @param array $options
     * @return $this
     */
    public function withOptions(array $options): self
    {
        return tap($this, function () use ($options) {
            $this->options = array_replace_recursive(
                array_merge_recursive($this->options, Arr::only($options, $this->mergableOptions)),
                $options
            );
        });
    }

    /**
     * Add new middleware the client handler stack.
     *
     * @param callable $middleware
     * @return $this
     */
    public function withMiddleware(callable $middleware): self
    {
        $this->middleware->push($middleware);

        return $this;
    }

    /**
     * Add new request middleware the client handler stack.
     *
     * @param callable $middleware
     * @return $this
     */
    public function withRequestMiddleware(callable $middleware): self
    {
        $this->middleware->push(Middleware::mapRequest($middleware));

        return $this;
    }

    /**
     * Add new response middleware the client handler stack.
     *
     * @param callable $middleware
     * @return $this
     */
    public function withResponseMiddleware(callable $middleware): self
    {
        $this->middleware->push(Middleware::mapResponse($middleware));

        return $this;
    }

    /**
     * Add a new "before sending" callback to the request.
     *
     * @param callable $callback
     * @return $this
     */
    public function beforeSending(callable $callback): self
    {
        return tap($this, function () use ($callback) {
            $this->beforeSendingCallbacks[] = $callback;
        });
    }

    /**
     * Throw an exception if a server or client error occurs.
     *
     * @param callable|null $callback
     * @return $this
     */
    public function throw(callable $callback = null): self
    {
        $this->throwCallback = $callback ?: static fn() => null;

        return $this;
    }

    /**
     * Throw an exception if a server or client error occurred and the given condition evaluates to true.
     *
     * @param callable|bool $condition
     * @return $this
     */
    public function throwIf(callable|bool $condition): self
    {
        if (is_callable($condition)) {
            $this->throwIfCallback = $condition;
        }

        return $condition ? $this->throw(func_get_args()[1] ?? null) : $this;
    }

    /**
     * Throw an exception if a server or client error occurred and the given condition evaluates to false.
     *
     * @param bool $condition
     * @return $this
     */
    public function throwUnless(bool $condition): self
    {
        return $this->throwIf(!$condition);
    }

    /**
     * Dump the request before sending.
     *
     * @return $this
     */
    public function dump(): self
    {
        $values = func_get_args();

        return $this->beforeSending(function (Request $request, array $options) use ($values) {
            foreach (array_merge($values, [$request, $options]) as $value) {
                if (function_exists('dump')) {
                    dump($value);
                }
            }
        });
    }

    /**
     * Issue a GET request to the given URL.
     *
     * @param string $url
     * @param array|string|null $query
     * @return PromiseInterface|Response
     */
    public function get(string $url, mixed $query = null): PromiseInterface|Response
    {
        return $this->send('GET', $url, func_num_args() === 1 ? [] : [
            'query' => $query,
        ]);
    }

    /**
     * Issue a HEAD request to the given URL.
     *
     * @param string $url
     * @param array|string|null $query
     * @return PromiseInterface|Response
     */
    public function head(string $url, mixed $query = null): PromiseInterface|Response
    {
        return $this->send('HEAD', $url, func_num_args() === 1 ? [] : [
            'query' => $query,
        ]);
    }

    /**
     * Issue a POST request to the given URL.
     *
     * @param string $url
     * @param mixed $data
     * @return PromiseInterface|Response
     */
    public function post(string $url, mixed $data = []): PromiseInterface|Response
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a PATCH request to the given URL.
     *
     * @param string $url
     * @param array $data
     * @return PromiseInterface|Response
     */
    public function patch(string $url, mixed $data = []): PromiseInterface|Response
    {
        return $this->send('PATCH', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a PUT request to the given URL.
     *
     * @param string $url
     * @param array $data
     * @return PromiseInterface|Response
     */
    public function put(string $url, mixed $data = []): PromiseInterface|Response
    {
        return $this->send('PUT', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Issue a DELETE request to the given URL.
     *
     * @param string $url
     * @param array $data
     * @return PromiseInterface|Response
     */
    public function delete(string $url, mixed $data = []): PromiseInterface|Response
    {
        return $this->send('DELETE', $url, empty($data) ? [] : [
            $this->bodyFormat => $data,
        ]);
    }

    /**
     * Send a pool of asynchronous requests concurrently.
     *
     * @param callable $callback
     * @return array<array-key, Response>
     */
    public function pool(callable $callback): array
    {
        $results = [];

        $requests = tap(new Pool($this->factory), $callback)->getRequests();

        foreach ($requests as $key => $item) {
            $results[$key] = $item instanceof static ? $item->getPromise()?->wait() : $item->wait();
        }

        return $results;
    }

    /**
     * Send the request to the given URL.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return PromiseInterface|Response
     */
    public function send(string $method, string $url, array $options = []): PromiseInterface|Response
    {
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $url = ltrim(rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/'), '/');
        }

        $url = $this->expandUrlParameters($url);

        $options = $this->parseHttpOptions($options);

        [$this->pendingBody, $this->pendingFiles] = [null, []];

        if ($this->async) {
            return $this->makePromise($method, $url, $options);
        }

        $shouldRetry = null;

        return retry($this->tries ?? 1, function ($attempt) use ($method, $url, $options, &$shouldRetry) {
            try {
                return tap($this->newResponse($this->sendRequest($method, $url, $options)), function ($response) use ($attempt, &$shouldRetry) {
                    $this->populateResponse($response);

                    $this->dispatchResponseReceivedEvent($response);

                    if (!$response->successful()) {
                        try {
                            $shouldRetry = !$this->retryWhenCallback || call_user_func($this->retryWhenCallback, $response->toException(), $this);
                        } catch (Exception $exception) {
                            $shouldRetry = false;

                            throw $exception;
                        }

                        if ((isset($this->throwCallback) && $this->throwCallback) &&
                            ($this->throwIfCallback === null ||
                                call_user_func($this->throwIfCallback, $response))) {
                            $response->throw($this->throwCallback);
                        }

                        if ($attempt < $this->tries && $shouldRetry) {
                            $response->throw();
                        }

                        if ($this->tries > 1 && $this->retryThrow) {
                            $response->throw();
                        }
                    }
                });
            } catch (ConnectException $e) {
                $this->dispatchConnectionFailedEvent();

                throw new ConnectionException($e->getMessage(), 0, $e);
            }
        }, $this->retryDelay ?? 100, function ($exception) use (&$shouldRetry) {
            $result = $shouldRetry ?? ($this->retryWhenCallback ? call_user_func($this->retryWhenCallback, $exception, $this) : true);

            $shouldRetry = null;

            return $result;
        });
    }

    /**
     * Substitute the URL parameters in the given URL.
     *
     * @param string $url
     * @return string
     */
    protected function expandUrlParameters(string $url): string
    {
        return UriTemplate::expand($url, $this->urlParameters);
    }

    /**
     * Parse the given HTTP options and set the appropriate additional options.
     *
     * @param array $options
     * @return array
     */
    protected function parseHttpOptions(array $options): array
    {
        if (isset($options[$this->bodyFormat])) {
            if ($this->bodyFormat === 'multipart') {
                $options[$this->bodyFormat] = $this->parseMultipartBodyFormat($options[$this->bodyFormat]);
            } elseif ($this->bodyFormat === 'body') {
                $options[$this->bodyFormat] = $this->pendingBody;
            }

            if (is_array($options[$this->bodyFormat])) {
                $options[$this->bodyFormat] = array_merge(
                    $options[$this->bodyFormat], $this->pendingFiles
                );
            }
        } else {
            $options[$this->bodyFormat] = $this->pendingBody;
        }

        return collect($options)->map(function ($value, $key) {
            if ($key === 'json' && $value instanceof JsonSerializable) {
                return $value;
            }

            return $value instanceof Arrayable ? $value->toArray() : $value;
        })->all();
    }

    /**
     * Parse multi-part form data.
     *
     * @param array $data
     * @return array
     */
    protected function parseMultipartBodyFormat(array $data): array
    {
        return collect($data)->map(function ($value, $key) {
            return is_array($value) ? $value : ['name' => $key, 'contents' => $value];
        })->values()->all();
    }

    /**
     * Send an asynchronous request to the given URL.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return PromiseInterface
     * @throws Exception
     */
    protected function makePromise(string $method, string $url, array $options = []): PromiseInterface
    {
        return $this->promise = $this->sendRequest($method, $url, $options)
            ->then(function (MessageInterface $message) {
                return tap($this->newResponse($message), function ($response) {
                    $this->populateResponse($response);
                    $this->dispatchResponseReceivedEvent($response);
                });
            })
            ->otherwise(function (OutOfBoundsException|TransferException $e) {
                if ($e instanceof ConnectException) {
                    $this->dispatchConnectionFailedEvent();
                }

                return $e instanceof RequestException && $e->hasResponse() ? $this->populateResponse($this->newResponse($e->getResponse())) : $e;
            });
    }

    /**
     * Send a request either synchronously or asynchronously.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return MessageInterface|PromiseInterface
     *
     * @throws Exception
     */
    protected function sendRequest(string $method, string $url, array $options = []): MessageInterface|PromiseInterface
    {
        $clientMethod = $this->async ? 'requestAsync' : 'request';

        $miniData = $this->parseRequestData($method, $url, $options);

        $onStats = function ($transferStats) {
            if (($callback = ($this->options['on_stats'] ?? false)) instanceof Closure) {
                $transferStats = $callback($transferStats) ?: $transferStats;
            }

            $this->transferStats = $transferStats;
        };

        $mergedOptions = $this->normalizeRequestOptions($this->mergeOptions([
            'mini_data' => $miniData,
            'on_stats' => $onStats,
        ], $options));

        return $this->buildClient()->$clientMethod($method, $url, $mergedOptions);
    }

    /**
     * Get the request data as an array so that we can attach it to the request for convenient assertions.
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return array
     */
    protected function parseRequestData(string $method, string $url, array $options): array
    {
        if ($this->bodyFormat === 'body') {
            return [];
        }

        $miniData = $options[$this->bodyFormat] ?? $options['query'] ?? [];

        $urlString = Str::of($url);

        if (empty($miniData) && $method === 'GET' && $urlString->contains('?')) {
            $miniData = (string)$urlString->after('?');
        }

        if (is_string($miniData)) {
            parse_str($miniData, $parsedData);

            $miniData = is_array($parsedData) ? $parsedData : [];
        }

        if ($miniData instanceof JsonSerializable) {
            $miniData = $miniData->jsonSerialize();
        }

        return is_array($miniData) ? $miniData : [];
    }

    /**
     * Normalize the given request options.
     *
     * @param array $options
     * @return array
     */
    protected function normalizeRequestOptions(array $options): array
    {
        foreach ($options as $key => $value) {
            $options[$key] = match (true) {
                is_array($value) => $this->normalizeRequestOptions($value),
                $value instanceof Stringable => $value->toString(),
                default => $value,
            };
        }

        return $options;
    }

    /**
     * Populate the given response with additional data.
     *
     * @param Response $response
     * @return Response
     */
    protected function populateResponse(Response $response): Response
    {
        $response->cookies = $this->cookies;

        $response->transferStats = $this->transferStats;

        return $response;
    }

    /**
     * Build the Guzzle client.
     *
     * @return Client
     */
    public function buildClient(): Client
    {
        return $this->client ?? $this->createClient($this->buildHandlerStack());
    }

    /**
     * Determine if a reusable client is required.
     *
     * @return bool
     */
    protected function requestsReusableClient(): bool
    {
        return !is_null($this->client) || $this->async;
    }

    /**
     * Retrieve a reusable Guzzle client.
     *
     * @return Client
     */
    protected function getReusableClient(): Client
    {
        return $this->client = $this->client ?: $this->createClient($this->buildHandlerStack());
    }

    /**
     * Create new Guzzle client.
     *
     * @param HandlerStack $handlerStack
     * @return Client
     */
    public function createClient(HandlerStack $handlerStack): Client
    {
        return new Client([
            'handler' => $handlerStack,
            'cookies' => true,
        ]);
    }

    /**
     * Build the Guzzle client handler stack.
     *
     * @return HandlerStack
     */
    public function buildHandlerStack(): HandlerStack
    {
        return $this->pushHandlers(HandlerStack::create($this->handler));
    }

    /**
     * Add the necessary handlers to the given handler stack.
     *
     * @param HandlerStack $handlerStack
     * @return HandlerStack
     */
    public function pushHandlers(HandlerStack $handlerStack): HandlerStack
    {
        return tap($handlerStack, function ($stack) {
            $stack->push($this->buildBeforeSendingHandler());

            $this->middleware->each(function ($middleware) use ($stack) {
                $stack->push($middleware);
            });

            $stack->push($this->buildRecorderHandler());
            $stack->push($this->buildStubHandler());
        });
    }

    /**
     * Build the before sending handler.
     *
     * @return Closure
     */
    public function buildBeforeSendingHandler(): callable
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($this->runBeforeSendingCallbacks($request, $options), $options);
            };
        };
    }

    /**
     * Build the recorder handler.
     *
     * @return Closure
     */
    public function buildRecorderHandler(): callable
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                return $handler($request, $options)->then(function ($response) use ($request, $options) {
                    $this->factory?->recordRequestResponsePair(
                        (new Request($request))->withData($options['mini_data']),
                        $this->newResponse($response)
                    );

                    return $response;
                });
            };
        };
    }

    /**
     * Build the stub handler.
     *
     * @return Closure
     */
    public function buildStubHandler(): callable
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                $response = ($this->stubCallbacks ?? collect())
                    ->map
                    ->__invoke((new Request($request))->withData($options['mini_data']), $options)
                    ->filter()
                    ->first();

                if (is_null($response)) {
                    if ($this->preventStrayRequests) {
                        throw new RuntimeException('Attempted request to [' . (string)$request->getUri() . '] without a matching fake.');
                    }

                    return $handler($request, $options);
                }

                $response = is_array($response) ? Factory::response($response) : $response;

                $sink = $options['sink'] ?? null;

                if ($sink) {
                    $response->then($this->sinkStubHandler($sink));
                }

                return $response;
            };
        };
    }

    /**
     * Get the sink stub handler callback.
     *
     * @param string $sink
     * @return Closure
     */
    protected function sinkStubHandler(mixed $sink): callable
    {
        return function ($response) use ($sink) {
            $body = $response->getBody()->getContents();

            if (is_string($sink)) {
                file_put_contents($sink, $body);

                return;
            }

            fwrite($sink, $body);
            rewind($sink);
        };
    }

    /**
     * Execute the "before sending" callbacks.
     *
     * @param RequestInterface $request
     * @param array $options
     * @return RequestInterface
     */
    public function runBeforeSendingCallbacks(RequestInterface $request, array $options): RequestInterface
    {
        return tap($request, function (&$request) use ($options) {
            $this->beforeSendingCallbacks->each(function ($callback) use (&$request, $options) {
                $callbackResult = $callback((new Request($request))->withData($options['mini_data']), $options, $this);

                if ($callbackResult instanceof RequestInterface) {
                    $request = $callbackResult;
                } elseif ($callbackResult instanceof Request) {
                    $request = $callbackResult->toPsrRequest();
                }
            });
        });
    }

    /**
     * Replace the given options with the current request options.
     *
     * @param array ...$options
     * @return array
     */
    public function mergeOptions(...$options): array
    {
        return array_replace_recursive(
            array_merge_recursive($this->options, Arr::only($options, $this->mergableOptions)),
            ...$options
        );
    }

    /**
     * Create a new response instance using the given PSR response.
     *
     * @param MessageInterface $response
     * @return Response
     */
    protected function newResponse(MessageInterface $response): Response
    {
        return new Response($response);
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param callable $callback
     * @return $this
     */
    public function stub($callback): self
    {
        $this->stubCallbacks = collect($callback);

        return $this;
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
     * Toggle asynchronicity in requests.
     *
     * @param bool $async
     * @return $this
     */
    public function async(bool $async = true): self
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Retrieve the pending request promise.
     *
     * @return PromiseInterface|null
     */
    public function getPromise(): ?PromiseInterface
    {
        return $this->promise;
    }

    /**
     * Dispatch the RequestSending event if a dispatcher is available.
     *
     * @return void
     */
    protected function dispatchRequestSendingEvent(): void
    {
        if ($dispatcher = $this->factory?->getDispatcher()) {
            $dispatcher->dispatch(new RequestSending($this->request));
        }
    }

    /**
     * Dispatch the ResponseReceived event if a dispatcher is available.
     *
     * @param Response $response
     * @return void
     */
    protected function dispatchResponseReceivedEvent(Response $response): void
    {
        if (!$this->request || !($dispatcher = $this->factory?->getDispatcher())) {
            return;
        }

        $dispatcher->dispatch(new ResponseReceived($this->request, $response));
    }

    /**
     * Dispatch the ConnectionFailed event if a dispatcher is available.
     *
     * @return void
     */
    protected function dispatchConnectionFailedEvent(): void
    {
        if ($dispatcher = $this->factory?->getDispatcher()) {
            $dispatcher->dispatch(new ConnectionFailed($this->request));
        }
    }

    /**
     * Set the client instance.
     *
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Create a new client instance using the given handler.
     *
     * @param callable $handler
     * @return $this
     */
    public function setHandler(callable $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Get the pending request options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
