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

/**
 * @mixin Factory
 */
class Pool
{
    /**
     * The factory instance.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * The client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The pool of requests.
     *
     * @var array
     */
    protected $pool = [];

    /**
     * Create a new requests pool.
     */
    public function __construct(Factory $factory = null)
    {
        $this->factory = $factory ?: new Factory();

        $this->client = $this->factory->buildClient();
    }

    /**
     * Add a request to the pool with a numeric index.
     *
     * @param string $method
     * @param array $parameters
     * @return PendingRequest
     */
    public function __call($method, $parameters)
    {
        return $this->pool[] = $this->asyncRequest()->{$method}(...$parameters);
    }

    /**
     * Add a request to the pool with a key.
     *
     * @return PendingRequest
     */
    public function as(string $key)
    {
        return $this->pool[$key] = $this->asyncRequest();
    }

    /**
     * Retrieve the requests in the pool.
     *
     * @return array
     */
    public function getRequests()
    {
        return $this->pool;
    }

    /**
     * Retrieve a new async pending request.
     *
     * @return PendingRequest
     */
    protected function asyncRequest()
    {
        return $this->factory->setClient($this->client)->async();
    }
}
