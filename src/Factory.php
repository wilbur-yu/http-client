<?php

declare(strict_types=1);
/**
 * This file is part of http-client.
 *
 * @link     https://github.com/friendsofhyperf/http-client
 * @document https://github.com/friendsofhyperf/http-client/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\Http\Client;

use Closure;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Str;
use Hyperf\Utils\Traits\Macroable;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @method PendingRequest accept(string $contentType)
 * @method PendingRequest acceptJson()
 * @method PendingRequest asForm()
 * @method PendingRequest asJson()
 * @method PendingRequest asMultipart()
 * @method PendingRequest async()
 * @method PendingRequest attach(array|string $name, string $contents = '', null|string $filename = null, array $headers = [])
 * @method PendingRequest baseUrl(string $url)
 * @method PendingRequest beforeSending(callable $callback)
 * @method PendingRequest bodyFormat(string $format)
 * @method PendingRequest contentType(string $contentType)
 * @method PendingRequest dd()
 * @method PendingRequest dump()
 * @method PendingRequest retry(int $times, int $sleep = 0)
 * @method PendingRequest sink(resource|string $to)
 * @method PendingRequest stub(callable $callback)
 * @method PendingRequest timeout(int $seconds)
 * @method PendingRequest withBasicAuth(string $username, string $password)
 * @method PendingRequest withBody(resource|string $content, string $contentType)
 * @method PendingRequest withCookies(array $cookies, string $domain)
 * @method PendingRequest withDigestAuth(string $username, string $password)
 * @method PendingRequest withHeaders(array $headers)
 * @method PendingRequest withMiddleware(callable $middleware)
 * @method PendingRequest withOptions(array $options)
 * @method PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method PendingRequest withUserAgent(string $userAgent)
 * @method PendingRequest withoutRedirecting()
 * @method PendingRequest withoutVerifying()
 * @method array pool(callable $callback)
 * @method Response delete(string $url, array $data = [])
 * @method Response get(string $url, null|array|string $query = null)
 * @method Response head(string $url, null|array|string $query = null)
 * @method Response patch(string $url, array $data = [])
 * @method Response post(string $url, array $data = [])
 * @method Response put(string $url, array $data = [])
 * @method Response send(string $method, string $url, array $options = [])
 *
 * @see PendingRequest
 */
class Factory
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The event dispatcher implementation.
     *
     * @var null|EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * The stub callables that will handle requests.
     *
     * @var Collection
     */
    protected $stubCallbacks;

    /**
     * Indicates if the factory is recording requests and responses.
     *
     * @var bool
     */
    protected $recording = false;

    /**
     * The recorded response array.
     *
     * @var array
     */
    protected $recorded = [];

    /**
     * All created response sequences.
     *
     * @var array
     */
    protected $responseSequences = [];

    /**
     * Create a new factory instance.
     */
    public function __construct(EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;

        $this->stubCallbacks = collect();
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
            $request->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
    }

    /**
     * Create a new response instance for use during stubbing.
     *
     * @param array|string $body
     * @param int $status
     * @param array $headers
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public static function response($body = null, $status = 200, $headers = [])
    {
        if (is_array($body)) {
            $body = json_encode($body);

            $headers['Content-Type'] = 'application/json';
        }

        $response = new Psr7Response($status, $headers, $body);

        return class_exists(GuzzleHttp\Promise\Create::class)
            ? \GuzzleHttp\Promise\Create::promiseFor($response)
            : \GuzzleHttp\Promise\promise_for($response);
    }

    /**
     * Get an invokable object that returns a sequence of responses in order for use during stubbing.
     *
     * @return \Illuminate\Http\Client\ResponseSequence
     */
    public function sequence(array $responses = [])
    {
        return $this->responseSequences[] = new ResponseSequence($responses);
    }

    /**
     * Register a stub callable that will intercept requests and be able to return stub responses.
     *
     * @param array|callable $callback
     * @return $this
     */
    public function fake($callback = null)
    {
        $this->record();

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
            $callback instanceof Closure
                    ? $callback
                    : function () use ($callback) {
                        return $callback;
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
    public function fakeSequence($url = '*')
    {
        return tap($this->sequence(), function ($sequence) use ($url) {
            $this->fake([$url => $sequence]);
        });
    }

    /**
     * Stub the given URL using the given callback.
     *
     * @param string $url
     * @param callable|\GuzzleHttp\Promise\PromiseInterface|Response $callback
     * @return $this
     */
    public function stubUrl($url, $callback)
    {
        return $this->fake(function ($request, $options) use ($url, $callback) {
            if (! Str::is(Str::start($url, '*'), $request->url())) {
                return;
            }

            return $callback instanceof Closure || $callback instanceof ResponseSequence
                        ? $callback($request, $options)
                        : $callback;
        });
    }

    /**
     * Record a request response pair.
     *
     * @param Request $request
     * @param Response $response
     */
    public function recordRequestResponsePair($request, $response)
    {
        if ($this->recording) {
            $this->recorded[] = [$request, $response];
        }
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     *
     * @param callable $callback
     */
    public function assertSent($callback)
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
     */
    public function assertSentInOrder($callbacks)
    {
        $this->assertSentCount(count($callbacks));

        foreach ($callbacks as $index => $url) {
            $callback = is_callable($url) ? $url : function ($request) use ($url) {
                return $request->url() == $url;
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
     */
    public function assertNotSent($callback)
    {
        PHPUnit::assertFalse(
            $this->recorded($callback)->count() > 0,
            'Unexpected request was recorded.'
        );
    }

    /**
     * Assert that no request / response pair was recorded.
     */
    public function assertNothingSent()
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
     */
    public function assertSentCount($count)
    {
        PHPUnit::assertCount($count, $this->recorded);
    }

    /**
     * Assert that every created response sequence is empty.
     */
    public function assertSequencesAreEmpty()
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
     * @param callable $callback
     * @return \Illuminate\Support\Collection
     */
    public function recorded($callback = null)
    {
        if (empty($this->recorded)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->recorded)->filter(function ($pair) use ($callback) {
            return $callback($pair[0], $pair[1]);
        });
    }

    /**
     * Get the current event dispatcher implementation.
     *
     * @return null|\Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Begin recording request / response pairs.
     *
     * @return $this
     */
    protected function record()
    {
        $this->recording = true;

        return $this;
    }

    /**
     * Create a new pending request instance for this factory.
     *
     * @return PendingRequest
     */
    protected function newPendingRequest()
    {
        return new PendingRequest($this);
    }
}
