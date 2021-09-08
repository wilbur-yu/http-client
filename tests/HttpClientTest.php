<?php

declare(strict_types=1);
/**
 * This file is part of http-client.
 *
 * @link     https://github.com/friendsofhyperf/http-client
 * @document https://github.com/friendsofhyperf/http-client/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\Http\Client\Tests;

use FriendsOfHyperf\Http\Client\Http;

/**
 * @internal
 * @coversNothing
 */
class HttpClientTest extends TestCase
{
    public function testOk()
    {
        $response = Http::get('http://www.baidu.com');
        $this->assertTrue($response->ok());
    }

    public function testFailed()
    {
        $response = Http::get('http://laravel.com/test-missing-page');
        $this->assertTrue($response->clientError());

        $this->expectException(\FriendsOfHyperf\Http\Client\RequestException::class);
        $response->throw();
    }

    public function testBuildClient()
    {
        $client = Http::buildClient();

        $this->assertInstanceOf(\GuzzleHttp\Client::class, $client);
    }
}
