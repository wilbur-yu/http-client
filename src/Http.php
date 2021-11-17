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

use Hyperf\Utils\Traits\Macroable;

/**
 * @method static \GuzzleHttp\Client buildClient()
 * @method static PendingRequest accept(string $contentType)
 * @method static PendingRequest acceptJson()
 * @method static PendingRequest asForm()
 * @method static PendingRequest asJson()
 * @method static PendingRequest asMultipart()
 * @method static PendingRequest async()
 * @method static PendingRequest attach(array|string $name, string $contents = '', null|string $filename = null, array $headers = [])
 * @method static PendingRequest baseUrl(string $url)
 * @method static PendingRequest beforeSending(callable $callback)
 * @method static PendingRequest bodyFormat(string $format)
 * @method static PendingRequest contentType(string $contentType)
 * @method static PendingRequest dd()
 * @method static PendingRequest dump()
 * @method static PendingRequest retry(int $times, int $sleep = 0)
 * @method static PendingRequest sink(resource|string $to)
 * @method static PendingRequest stub(callable $callback)
 * @method static PendingRequest timeout(int $seconds)
 * @method static PendingRequest withBasicAuth(string $username, string $password)
 * @method static PendingRequest withBody(resource|string $content, string $contentType)
 * @method static PendingRequest withCookies(array $cookies, string $domain)
 * @method static PendingRequest withDigestAuth(string $username, string $password)
 * @method static PendingRequest withHeaders(array $headers)
 * @method static PendingRequest withMiddleware(callable $middleware)
 * @method static PendingRequest withOptions(array $options)
 * @method static PendingRequest withToken(string $token, string $type = 'Bearer')
 * @method static PendingRequest withUserAgent(string $userAgent)
 * @method static PendingRequest withoutRedirecting()
 * @method static PendingRequest withoutVerifying()
 * @method static PendingRequest setClient(\GuzzleHttp\Client $client)
 * @method static array pool(callable $callback)
 * @method static Response delete(string $url, array $data = [])
 * @method static Response get(string $url, null|array|string $query = null)
 * @method static Response head(string $url, null|array|string $query = null)
 * @method static Response patch(string $url, array $data = [])
 * @method static Response post(string $url, array $data = [])
 * @method static Response put(string $url, array $data = [])
 * @method static Response send(string $method, string $url, array $options = [])
 *
 * @see PendingRequest
 */
class Http
{
    use Macroable {
        __call as macroCall;
    }

    public static function __callStatic($name, $arguments)
    {
        return make(Factory::class)->{$name}(...$arguments);
    }
}
