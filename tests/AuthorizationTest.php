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

    public function testSetCookie(): void
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['foo']), $this->equalTo(['bar']), $this->arrayHasKey('x-foo'))
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            function (Update $u): string { return 'dummy'; },
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry, 0);
        $authorization->setCookie($request, ['foo'], ['bar'], ['x-foo' => 'bar', 'exp' => 3600]);

        $cookie = $request->attributes->get('_mercure_authorization_cookies')[null];
        $this->assertNotNull($cookie->getValue());
        $this->assertSame(3600, $cookie->getExpiresTime());
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
        $request = Request::create('https://example.com');
        $authorization->clearCookie($request);

        $cookie = $request->attributes->get('_mercure_authorization_cookies')[null];
        $this->assertNull($cookie->getValue());
        $this->assertSame(1, $cookie->getExpiresTime());
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
        yield ['.example.com', 'https://foo.bar.baz.example.com', 'https://foo.bar.baz.qux.example.com'];
        yield ['.foo.bar.baz.example.com', 'https://mercure.foo.bar.baz.example.com', 'https://app.foo.bar.baz.example.com'];
        yield ['example.com', 'https://demo.example.com', 'https://example.com'];
        yield ['.example.com', 'https://mercure.example.com', 'https://app.example.com'];
        yield ['.example.com', 'https://example.com/.well-known/mercure', 'https://app.example.com'];
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

    public function testSetMultipleCookies(): void
    {
        $this->expectException(RuntimeException::class);

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
        $request = Request::create('https://example.com');
        $authorization->setCookie($request);
        $authorization->clearCookie($request);
    }
}
