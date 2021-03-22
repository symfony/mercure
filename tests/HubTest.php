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

namespace Symfony\Component\Mercure\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class HubTest extends TestCase
{
    public const URL = 'https://demo.mercure.rocks/.well-known/mercure';
    public const JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyIqIl0sInB1Ymxpc2giOlsiKiJdfX0.M1yJUov4a6oLrigTqBZQO_ohWUsg3Uz1bnLD4MIyWLo';
    public const AUTH_HEADER = 'Authorization: Bearer '.self::JWT;

    public function testPublish()
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options = []): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame(self::URL, $url);
            $this->assertSame(self::AUTH_HEADER, $options['normalized_headers']['authorization'][0]);
            $this->assertSame('topic=https%3A%2F%2Fdemo.mercure.rocks%2Fdemo%2Fbooks%2F1.jsonld&data=Hi+from+Symfony%21&private=on&id=id&retry=3', $options['body']);

            return new MockResponse('id');
        });

        $provider = new StaticTokenProvider(self::JWT);
        $hub = new Hub('default', self::URL, $provider, null, null, $httpClient);
        $id = $hub->publish(new Update(
            'https://demo.mercure.rocks/demo/books/1.jsonld',
            'Hi from Symfony!',
            true,
            'id',
            null,
            3
        ));

        $this->assertSame('id', $id);
    }

    public function testNetworkIssue()
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options = []): ResponseInterface {
            $this->assertSame('POST', $method);
            $this->assertSame(self::URL, $url);
            $this->assertSame(self::AUTH_HEADER, $options['normalized_headers']['authorization'][0]);
            $this->assertSame('topic=https%3A%2F%2Fdemo.mercure.rocks%2Fdemo%2Fbooks%2F1.jsonld&data=Hi+from+Symfony%21&private=on&id=id&retry=3', $options['body']);

            throw new TransportException('Ops');
        });

        $provider = new StaticTokenProvider(self::JWT);
        $hub = new Hub('default', self::URL, $provider, null, null, $httpClient);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send an update.');

        $hub->publish(new Update(
            'https://demo.mercure.rocks/demo/books/1.jsonld',
            'Hi from Symfony!',
            true,
            'id',
            null,
            3
        ));
    }

    public function testInvalidJwt()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided JWT is not valid');

        $provider = new StaticTokenProvider("invalid\r\njwt");
        $hub = new Hub('default', self::URL, $provider, null, null);

        $hub->publish(new Update('https://demo.mercure.rocks/demo/books/1.jsonld', 'Hi from Symfony!'));
    }
}
