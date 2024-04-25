<?php

namespace Mini\Facades;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Mini\Http\Factory;
use Mini\Http\ResponseSequence;

/**
 * @method static Factory globalMiddleware(callable $middleware)
 * @method static Factory globalRequestMiddleware(callable $middleware)
 * @method static Factory globalResponseMiddleware(callable $middleware)
 * @method static PromiseInterface response(array|string|null $body = null, int $status = 200, array $headers = [])
 * @method static ResponseSequence sequence(array $responses = [])
 * @method static Factory allowStrayRequests()
 * @method static void recordRequestResponsePair(\Mini\Http\Request $request, \Mini\Http\Response $response)
 * @method static void assertSent(callable $callback)
 * @method static void assertSentInOrder(array $callbacks)
 * @method static void assertNotSent(callable $callback)
 * @method static void assertNothingSent()
 * @method static void assertSentCount(int $count)
 * @method static void assertSequencesAreEmpty()
 * @method static \Mini\Support\Collection recorded(callable $callback = null)
 * @method static \Mini\Contracts\Events\Dispatcher|null getDispatcher()
 * @method static array getGlobalMiddleware()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static \Mini\Http\PendingRequest baseUrl(string $url)
 * @method static \Mini\Http\PendingRequest withBody(string $content, string $contentType = 'application/json')
 * @method static \Mini\Http\PendingRequest asJson()
 * @method static \Mini\Http\PendingRequest asForm()
 * @method static \Mini\Http\PendingRequest attach(string|array $name, string|resource $contents = '', string|null $filename = null, array $headers = [])
 * @method static \Mini\Http\PendingRequest asMultipart()
 * @method static \Mini\Http\PendingRequest bodyFormat(string $format)
 * @method static \Mini\Http\PendingRequest withQueryParameters(array $parameters)
 * @method static \Mini\Http\PendingRequest contentType(string $contentType)
 * @method static \Mini\Http\PendingRequest acceptJson()
 * @method static \Mini\Http\PendingRequest accept(string $contentType)
 * @method static \Mini\Http\PendingRequest withHeaders(array $headers)
 * @method static \Mini\Http\PendingRequest withHeader(string $name, mixed $value)
 * @method static \Mini\Http\PendingRequest replaceHeaders(array $headers)
 * @method static \Mini\Http\PendingRequest withBasicAuth(string $username, string $password)
 * @method static \Mini\Http\PendingRequest withDigestAuth(string $username, string $password)
 * @method static \Mini\Http\PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static \Mini\Http\PendingRequest withUserAgent(string|bool $userAgent)
 * @method static \Mini\Http\PendingRequest withUrlParameters(array $parameters = [])
 * @method static \Mini\Http\PendingRequest withCookies(array $cookies, string $domain)
 * @method static \Mini\Http\PendingRequest maxRedirects(int $max)
 * @method static \Mini\Http\PendingRequest withoutRedirecting()
 * @method static \Mini\Http\PendingRequest withoutVerifying()
 * @method static \Mini\Http\PendingRequest sink(string|resource $to)
 * @method static \Mini\Http\PendingRequest timeout(int $seconds)
 * @method static \Mini\Http\PendingRequest connectTimeout(int $seconds)
 * @method static \Mini\Http\PendingRequest retry(int $times, Closure|int $sleepMilliseconds = 0, callable|null $when = null, bool $throw = true)
 * @method static \Mini\Http\PendingRequest withOptions(array $options)
 * @method static \Mini\Http\PendingRequest withMiddleware(callable $middleware)
 * @method static \Mini\Http\PendingRequest withRequestMiddleware(callable $middleware)
 * @method static \Mini\Http\PendingRequest withResponseMiddleware(callable $middleware)
 * @method static \Mini\Http\PendingRequest beforeSending(callable $callback)
 * @method static \Mini\Http\PendingRequest throw(callable|null $callback = null)
 * @method static \Mini\Http\PendingRequest throwIf(callable|bool $condition, callable|null $throwCallback = null)
 * @method static \Mini\Http\PendingRequest throwUnless(bool $condition)
 * @method static \Mini\Http\PendingRequest dump()
 * @method static \Mini\Http\PendingRequest dd()
 * @method static \Mini\Http\Response get(string $url, array|string|null $query = null)
 * @method static \Mini\Http\Response head(string $url, array|string|null $query = null)
 * @method static \Mini\Http\Response post(string $url, array $data = [])
 * @method static \Mini\Http\Response patch(string $url, array $data = [])
 * @method static \Mini\Http\Response put(string $url, array $data = [])
 * @method static \Mini\Http\Response delete(string $url, array $data = [])
 * @method static array pool(callable $callback)
 * @method static \Mini\Http\Response send(string $method, string $url, array $options = [])
 * @method static \GuzzleHttp\Client buildClient()
 * @method static \GuzzleHttp\Client createClient(\GuzzleHttp\HandlerStack $handlerStack)
 * @method static \GuzzleHttp\HandlerStack buildHandlerStack()
 * @method static \GuzzleHttp\HandlerStack pushHandlers(\GuzzleHttp\HandlerStack $handlerStack)
 * @method static Closure buildBeforeSendingHandler()
 * @method static Closure buildRecorderHandler()
 * @method static Closure buildStubHandler()
 * @method static \GuzzleHttp\Psr7\RequestInterface runBeforeSendingCallbacks(\GuzzleHttp\Psr7\RequestInterface $request, array $options)
 * @method static array mergeOptions(array ...$options)
 * @method static \Mini\Http\PendingRequest stub(callable $callback)
 * @method static \Mini\Http\PendingRequest async(bool $async = true)
 * @method static PromiseInterface|null getPromise()
 * @method static \Mini\Http\PendingRequest setClient(\GuzzleHttp\Client $client)
 * @method static \Mini\Http\PendingRequest setHandler(callable $handler)
 * @method static array getOptions()
 * @method static \Mini\Http\PendingRequest|mixed when(Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \Mini\Http\PendingRequest|mixed unless(Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 *
 * @see \Mini\Http\Factory
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Factory::class;
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param array|Closure|null $callback
     * @return Factory
     */
    public static function fake(array|Closure $callback = null): Factory
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($callback) {
            static::swap($fake->fake($callback));
        });
    }

    /**
     * Register a response sequence for the given URL pattern.
     *
     * @param string $urlPattern
     * @return ResponseSequence
     */
    public static function fakeSequence(string $urlPattern = '*'): ResponseSequence
    {
        $fake = tap(static::getFacadeRoot(), function ($fake) {
            static::swap($fake);
        });

        return $fake->fakeSequence($urlPattern);
    }

    /**
     * Indicate that an exception should be thrown if any request is not faked.
     *
     * @return Factory
     */
    public static function preventStrayRequests(): Factory
    {
        return tap(static::getFacadeRoot(), function ($fake) {
            static::swap($fake->preventStrayRequests());
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param string $url
     * @param callable|\Mini\Http\Response|PromiseInterface $callback
     * @return Factory
     */
    public static function stubUrl(string $url, callable|PromiseInterface|\Mini\Http\Response $callback): Factory
    {
        return tap(static::getFacadeRoot(), function ($fake) use ($url, $callback) {
            static::swap($fake->stubUrl($url, $callback));
        });
    }
}
