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
    public function testBase()
    {
        $response = Http::post('http://laravel.com/test-missing-page');

        $this->assertFalse($response->ok());

        $response = Http::post('http://www.baidu.com');

        $this->assertTrue($response->ok());
    }
}
