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

use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class AuthorizationTest extends TestCase
{
    public function testJwtLifetime(): void
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            function (Update $u): string { return 'dummy'; },
            new LcobucciFactory('secret', 'hmac.sha256', 3600)
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie(Request::create('https://example.com'));

        $payload = json_decode(base64_decode(explode('.', $cookie->getValue())[1], true), true);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertIsNumeric($payload['exp']);
    }

    public function testClearCookie(): void
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            function (Update $u): string { return 'dummy'; },
            new class() implements TokenFactoryInterface {
                public function create(array $subscribe = [], array $publish = [], array $additionalClaims = []): string
                {
                    return '';
                }
            }
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie($request = Request::create('https://example.com'));

        $response = new Response();
        $response->headers->setCookie($cookie);

        $authorization->clearCookie($request, $response);

        $this->assertNull($response->headers->getCookies()[0]->getValue());
        $this->assertSame(1, $response->headers->getCookies()[0]->getExpiresTime());
    }

    /**
     * @dataProvider provideApplicableCookieDomains
     */
    public function testApplicableCookieDomains(?string $expected, string $hubUrl, string $requestUrl): void
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            $hubUrl,
            new StaticTokenProvider('foo.bar.baz'),
            function (Update $u): string { return 'dummy'; },
            new LcobucciFactory('secret', 'hmac.sha256', 3600)
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie(Request::create($requestUrl));

        $this->assertSame($expected, $cookie->getDomain());
    }

    public function provideApplicableCookieDomains(): iterable
    {
        yield ['demo.example.com', 'https://demo.example.com', 'https://example.com'];
        yield ['mercure.example.com', 'https://mercure.example.com', 'https://app.example.com'];
        yield ['example.com', 'https://example.com/.well-known/mercure', 'https://app.example.com'];
        yield [null, 'https://example.com/.well-known/mercure', 'https://example.com'];
    }

    /**
     * @dataProvider provideNonApplicableCookieDomains
     */
    public function testNonApplicableCookieDomains(string $hubUrl, string $requestUrl): void
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            $hubUrl,
            new StaticTokenProvider('foo.bar.baz'),
            function (Update $u): string { return 'dummy'; },
            new LcobucciFactory('secret', 'hmac.sha256', 3600)
        ));

        $authorization = new Authorization($registry);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to create authorization cookie for a hub on the different second-level domain');

        $authorization->createCookie(Request::create($requestUrl));
    }

    public function provideNonApplicableCookieDomains(): iterable
    {
        yield ['https://demo.mercure.com', 'https://example.com'];
        yield ['https://mercure.internal.com', 'https://external.com'];
    }
}
