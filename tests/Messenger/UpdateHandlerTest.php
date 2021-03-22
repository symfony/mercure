<?php

/*
 * This file is part of the Mercure Component project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Mercure\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Messenger\UpdateHandler;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class UpdateHandlerTest extends TestCase
{
    public const URL = 'https://demo.mercure.rocks/.well-known/mercure';
    public const JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyIqIl0sInB1Ymxpc2giOlsiKiJdfX0.M1yJUov4a6oLrigTqBZQO_ohWUsg3Uz1bnLD4MIyWLo';
    public const AUTH_HEADER = 'Authorization: Bearer '.self::JWT;

    public function testInvoke()
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options = []): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame(self::URL, $url);
            $this->assertSame(self::AUTH_HEADER, $options['normalized_headers']['authorization'][0]);
            $this->assertSame('topic=https%3A%2F%2Fdemo.mercure.rocks%2Fdemo%2Fbooks%2F1.jsonld&data=Hi+from+Symfony%21&private=on&id=id&retry=3', $options['body']);

            return new MockResponse('id');
        });

        $provider = new StaticTokenProvider(self::JWT);
        $hub = new Hub(self::URL, $provider);
        $publisher = new Publisher($hub, $httpClient);
        $handler = new UpdateHandler($publisher);

        $handler(new Update(
            'https://demo.mercure.rocks/demo/books/1.jsonld',
            'Hi from Symfony!',
            true,
            'id',
            null,
            3
        ));
    }
}
