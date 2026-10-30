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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class AuthorizationTest extends TestCase
{
    public function testJwtLifetime()
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            new LcobucciFactory('looooooooooooongenoughtestsecret', 'hmac.sha256', 3600, protocolVersion: ProtocolVersion::Legacy)
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie(Request::create('https://example.com'));

        $payload = json_decode(base64_decode(explode('.', $cookie->getValue())[1], true), true);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertIsNumeric($payload['exp']);
    }

    /**
     * @group legacy
     */
    public function testSetCookie()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                [new Grant([Grant::ACTION_SUBSCRIBE], ['foo']), new Grant([Grant::ACTION_PUBLISH], ['bar'])],
                $this->arrayHasKey('x-foo')
            )
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry, 0, Cookie::SAMESITE_LAX);
        $authorization->setCookie($request, ['foo'], ['bar'], additionalClaims: ['x-foo' => 'bar']);

        $cookie = $request->attributes->get('_mercure_authorization_cookies')[''];
        $this->assertNotNull($cookie->getValue());
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testSetCookieWithPublishGrant()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                [new Grant([Grant::ACTION_SUBSCRIBE], ['foo']), new Grant([Grant::ACTION_PUBLISH], ['bar'])],
                $this->arrayHasKey('x-foo')
            )
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry, 0, Cookie::SAMESITE_LAX);
        $authorization->setCookie($request, [new Grant([Grant::ACTION_SUBSCRIBE], ['foo']), new Grant([Grant::ACTION_PUBLISH], ['bar'])], additionalClaims: ['x-foo' => 'bar']);

        $cookie = $request->attributes->get('_mercure_authorization_cookies')[''];
        $this->assertNotNull($cookie->getValue());
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testSetCookieWithTopicShorthand()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with([new Grant([Grant::ACTION_SUBSCRIBE], ['foo'])], $this->anything())
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry);
        $authorization->setCookie($request, ['foo']);

        $this->assertNotNull($request->attributes->get('_mercure_authorization_cookies')['']->getValue());
    }

    public function testSetCookieWithStringShorthand()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with([new Grant([Grant::ACTION_SUBSCRIBE], ['foo'])], $this->anything())
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry);
        $authorization->setCookie($request, 'foo');

        $this->assertNotNull($request->attributes->get('_mercure_authorization_cookies')['']->getValue());
    }

    public function testSetCookieWithGrantShapedArray()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with([new Grant([Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], ['foo'], 'x')], $this->anything())
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry);
        $authorization->setCookie($request, [['actions' => [Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], 'topics' => ['foo'], 'payload' => 'x']]);

        $this->assertNotNull($request->attributes->get('_mercure_authorization_cookies')['']->getValue());
    }

    public function testClearCookie()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            new class implements TokenFactoryInterface {
                public function create(array $grants = [], array $additionalClaims = []): string
                {
                    return '';
                }
            }
        ));

        $authorization = new Authorization($registry);
        $request = Request::create('https://example.com');
        $authorization->clearCookie($request);

        $cookie = $request->attributes->get('_mercure_authorization_cookies')[''];
        $this->assertNull($cookie->getValue());
        $this->assertSame(1, $cookie->getExpiresTime());
    }

    /**
     * @dataProvider provideApplicableCookieDomains
     */
    public function testApplicableCookieDomains(?string $expected, string $hubUrl, string $requestUrl)
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            $hubUrl,
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            new LcobucciFactory('looooooooooooongenoughtestsecret', 'hmac.sha256', 3600, protocolVersion: ProtocolVersion::Legacy)
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
    public function testNonApplicableCookieDomains(string $hubUrl, string $requestUrl)
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            $hubUrl,
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            new LcobucciFactory('looooooooooooongenoughtestsecret', 'hmac.sha256', 3600, protocolVersion: ProtocolVersion::Legacy)
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

    public function testSetMultipleCookies()
    {
        $this->expectException(RuntimeException::class);

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            new class implements TokenFactoryInterface {
                public function create(array $grants = [], array $additionalClaims = []): string
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

    public function testSetCookieTwiceMergesGrants()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));
        $request = Request::create('https://example.com');

        $authorization->setCookie($request, ['notifications', 'shared']);
        $authorization->setCookie($request, [
            new Grant([Grant::ACTION_SUBSCRIBE], ['exact' => ['shared', 'books/1'], 'urlpattern' => ['/books/*']]),
            new Grant([Grant::ACTION_SUBSCRIBE], ['books/2'], 'payload'),
            new Grant([Grant::ACTION_PUBLISH], ['books/1']),
        ]);

        $this->assertCount(2, $tokenFactory->calls);
        $this->assertEquals([
            new Grant([Grant::ACTION_SUBSCRIBE], ['exact' => ['notifications', 'shared', 'books/1'], 'urlpattern' => ['/books/*']]),
            new Grant([Grant::ACTION_SUBSCRIBE], ['books/2'], 'payload'),
            new Grant([Grant::ACTION_PUBLISH], ['books/1']),
        ], $tokenFactory->calls[1]['grants']);

        $cookies = $request->attributes->get('_mercure_authorization_cookies');
        $this->assertCount(1, $cookies);
        $this->assertSame('token-2', $cookies['']->getValue());
    }

    public function testSetCookieTwiceMergesAdditionalClaims()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));
        $request = Request::create('https://example.com');

        $authorization->setCookie($request, ['foo'], additionalClaims: ['x-foo' => 'foo', 'x-shared' => ['a' => 1]]);
        $authorization->setCookie($request, ['bar'], additionalClaims: ['x-bar' => 'bar', 'x-shared' => ['a' => 1]]);

        $claims = $tokenFactory->calls[1]['additionalClaims'];
        $this->assertSame('foo', $claims['x-foo']);
        $this->assertSame('bar', $claims['x-bar']);
        $this->assertSame(['a' => 1], $claims['x-shared']);
        $this->assertArrayHasKey('exp', $claims);
    }

    public function testSetCookieTwiceKeepsAnExplicitExpiration()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));
        $request = Request::create('https://example.com');
        $expiration = new \DateTimeImmutable('+1 day');

        $authorization->setCookie($request, ['foo'], additionalClaims: ['exp' => $expiration]);
        $authorization->setCookie($request, ['bar'], additionalClaims: ['exp' => new \DateTimeImmutable('@'.$expiration->format('U.u'))]);
        $authorization->setCookie($request, ['baz']);

        $this->assertSame($expiration, $tokenFactory->calls[2]['additionalClaims']['exp']);
        $this->assertSame($expiration->getTimestamp(), $request->attributes->get('_mercure_authorization_cookies')['']->getExpiresTime());
    }

    public function testSetCookieTwiceKeepsTheEarliestExpiration()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));
        $request = Request::create('https://example.com');
        $earliest = new \DateTimeImmutable('+1 hour');

        $authorization->setCookie($request, ['foo'], additionalClaims: ['exp' => new \DateTimeImmutable('+2 hours')]);
        $authorization->setCookie($request, ['bar'], additionalClaims: ['exp' => $earliest]);
        $authorization->setCookie($request, ['baz'], additionalClaims: ['exp' => new \DateTimeImmutable('+1 hour')]);

        $this->assertSame($earliest, $tokenFactory->calls[2]['additionalClaims']['exp']);
    }

    public function testSetCookieTwiceKeepsTheEarliestExpirationTimestamp()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));
        $request = Request::create('https://example.com');

        $authorization->setCookie($request, ['foo'], additionalClaims: ['exp' => 1_900_000_001]);
        $authorization->setCookie($request, ['bar'], additionalClaims: ['exp' => 1_900_000_000]);

        $this->assertSame(1_900_000_000, $tokenFactory->calls[1]['additionalClaims']['exp']);
    }

    public function testSetCookieTwiceWithExpirationsOfDifferentTypesThrows()
    {
        $authorization = new Authorization($this->createRegistry($this->createRecordingTokenFactory()));
        $request = Request::create('https://example.com');
        $authorization->setCookie($request, ['foo'], additionalClaims: ['exp' => new \DateTimeImmutable('+1 hour')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('different value for the "exp" claim');

        $authorization->setCookie($request, ['bar'], additionalClaims: ['exp' => 1_900_000_000]);
    }

    public function testSetCookieTwiceWithConflictingAdditionalClaimsThrows()
    {
        $authorization = new Authorization($this->createRegistry($this->createRecordingTokenFactory()));
        $request = Request::create('https://example.com');
        $authorization->setCookie($request, ['foo'], additionalClaims: ['sub' => 'alice']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The subscriber authorization cookie for the "default" hub has already been set with a different value for the "sub" claim during the same request.');

        $authorization->setCookie($request, ['bar'], additionalClaims: ['sub' => 'bob']);
    }

    public function testSetCookieForDifferentHubsDoesNotMerge()
    {
        $defaultFactory = $this->createRecordingTokenFactory();
        $otherFactory = $this->createRecordingTokenFactory();
        $registry = new HubRegistry(
            $this->createHub($defaultFactory),
            ['default' => $this->createHub($defaultFactory), 'other' => $this->createHub($otherFactory, 'https://other.example.com/.well-known/mercure')],
        );
        $authorization = new Authorization($registry);
        $request = Request::create('https://example.com');

        $authorization->setCookie($request, ['foo']);
        $authorization->setCookie($request, ['bar'], hub: 'other');

        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], ['foo'])], $defaultFactory->calls[0]['grants']);
        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], ['bar'])], $otherFactory->calls[0]['grants']);
        $this->assertCount(2, $request->attributes->get('_mercure_authorization_cookies'));
    }

    public function testSetCookieForTheDefaultHubAndForItsNameMerges()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $hub = $this->createHub($tokenFactory);
        $authorization = new Authorization(new HubRegistry($hub, ['default' => $hub]));
        $request = Request::create('https://example.com');

        $authorization->setCookie($request, ['foo']);
        $authorization->setCookie($request, ['bar'], hub: 'default');

        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], ['exact' => ['foo', 'bar']])], $tokenFactory->calls[1]['grants']);
        $cookies = $request->attributes->get('_mercure_authorization_cookies');
        $this->assertSame([''], array_keys($cookies));
        $this->assertSame('token-2', $cookies['']->getValue());
    }

    public function testSetCookieTwiceDoesNotMergeTheGrantsOfASingleCall()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));
        $request = Request::create('https://example.com');

        $authorization->setCookie($request, [new Grant([Grant::ACTION_SUBSCRIBE], ['a']), new Grant([Grant::ACTION_SUBSCRIBE], ['b'])]);
        $authorization->setCookie($request, [new Grant([Grant::ACTION_PUBLISH], ['x']), new Grant([Grant::ACTION_PUBLISH], ['y'])]);

        $this->assertEquals([
            new Grant([Grant::ACTION_SUBSCRIBE], ['a']),
            new Grant([Grant::ACTION_SUBSCRIBE], ['b']),
            new Grant([Grant::ACTION_PUBLISH], ['x']),
            new Grant([Grant::ACTION_PUBLISH], ['y']),
        ], $tokenFactory->calls[1]['grants']);
    }

    public function testSetCookieTwiceOnDifferentRequestsDoesNotMerge()
    {
        $tokenFactory = $this->createRecordingTokenFactory();
        $authorization = new Authorization($this->createRegistry($tokenFactory));

        $authorization->setCookie(Request::create('https://example.com'), ['foo']);
        $authorization->setCookie(Request::create('https://example.com'), ['bar']);

        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], ['bar'])], $tokenFactory->calls[1]['grants']);
    }

    public function testSetCookieAfterClearCookieThrows()
    {
        $authorization = new Authorization($this->createRegistry($this->createRecordingTokenFactory()));
        $request = Request::create('https://example.com');
        $authorization->clearCookie($request);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The subscriber authorization cookie for the "default" hub has already been set. You cannot set it two times during the same request.');

        $authorization->setCookie($request, ['foo']);
    }

    public function testSetCookieAfterClearCookieOnANamedHubThrows()
    {
        $hub = $this->createHub($this->createRecordingTokenFactory());
        $authorization = new Authorization(new HubRegistry($hub, ['foo' => $hub]));
        $request = Request::create('https://example.com');
        $authorization->clearCookie($request, 'foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The subscriber authorization cookie for the "foo" hub has already been set. You cannot set it two times during the same request.');

        $authorization->setCookie($request, ['bar'], hub: 'foo');
    }

    public function testSetCookieDoesNotMergeIntoACookieItDidNotSet()
    {
        $authorization = new Authorization($this->createRegistry($this->createRecordingTokenFactory()));
        $request = Request::create('https://example.com');
        $authorization->setCookie($request, ['foo']);
        $request->attributes->set('_mercure_authorization_cookies', ['' => Cookie::create('mercureAuthorization', 'external')]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The subscriber authorization cookie for the "default" hub has already been set. You cannot set it two times during the same request.');

        $authorization->setCookie($request, ['bar']);
    }

    /**
     * @group legacy
     */
    public function testSetNullCookieTopics()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with([], $this->arrayHasKey('x-foo'))
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $tokenFactory
        ));

        $request = Request::create('https://example.com');
        $authorization = new Authorization($registry);
        $authorization->setCookie($request, null, null, additionalClaims: ['x-foo' => 'bar']);

        $cookie = $request->attributes->get('_mercure_authorization_cookies')[''];
        $this->assertNotNull($cookie->getValue());
    }

    public function testCookieNameComesFromTheHub()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $this->createMock(TokenFactoryInterface::class),
            protocolVersion: ProtocolVersion::V1,
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie(Request::create('https://example.com'));

        $this->assertSame('__Secure-mercure_access_token', $cookie->getName());
    }

    public function testPrefixedCookieNameOnPlainHttpHubThrowsWithAHint()
    {
        $registry = new HubRegistry(new MockHub(
            'http://localhost/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $this->createMock(TokenFactoryInterface::class),
            protocolVersion: ProtocolVersion::V1,
        ));

        $authorization = new Authorization($registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('configure a prefix-less cookie name on the hub for plain-HTTP development');

        $authorization->createCookie(Request::create('http://localhost'));
    }

    public function testPrefixLessCookieNameOnPlainHttpHubWorks()
    {
        $registry = new HubRegistry(new MockHub(
            'http://localhost/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static fn (Update $u): string => 'dummy',
            $this->createMock(TokenFactoryInterface::class),
            cookieName: 'mercure_access_token',
            protocolVersion: ProtocolVersion::V1,
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie(Request::create('http://localhost'));

        $this->assertSame('mercure_access_token', $cookie->getName());
        $this->assertFalse($cookie->isSecure());
    }

    private function createRegistry(TokenFactoryInterface $tokenFactory): HubRegistry
    {
        return new HubRegistry($this->createHub($tokenFactory));
    }

    private function createHub(TokenFactoryInterface $tokenFactory, string $url = 'https://example.com/.well-known/mercure'): MockHub
    {
        return new MockHub($url, new StaticTokenProvider('foo.bar.baz'), static fn (Update $u): string => 'dummy', $tokenFactory, protocolVersion: ProtocolVersion::Legacy);
    }

    /**
     * @return TokenFactoryInterface&object{calls: list<array{grants: Grant[], additionalClaims: mixed[]}>}
     */
    private function createRecordingTokenFactory(): TokenFactoryInterface
    {
        return new class implements TokenFactoryInterface {
            public array $calls = [];

            public function create(array $grants = [], array $additionalClaims = []): string
            {
                $this->calls[] = ['grants' => $grants, 'additionalClaims' => $additionalClaims];

                return 'token-'.\count($this->calls);
            }
        };
    }
}
