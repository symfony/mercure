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

namespace Symfony\Component\Mercure\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Twig\MercureExtension;
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class MercureExtensionTest extends TestCase
{
    public function testMercure()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/');
        $requestStack->push($request);

        $extension = new MercureExtension($registry, new Authorization($registry), $requestStack);

        $url = $extension->mercure(['https://foo/bar'], ['subscribe' => ['https://foo/{id}']]);

        $this->assertSame('https://example.com/.well-known/mercure?topic=https%3A%2F%2Ffoo%2Fbar', $url);
        $this->assertInstanceOf(Cookie::class, $request->attributes->get('_mercure_authorization_cookies')['']);
    }

    public function testMercureLastEventId()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string {
                return 'dummy';
            },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/');
        $requestStack->push($request);

        $extension = new MercureExtension($registry, new Authorization($registry), $requestStack);

        $url = $extension->mercure(['https://foo/bar'], [
            'lastEventId' => 'urn:uuid:13697bc5-e3c6-48cf-99c8-9d64c26f1a2f',
        ]);

        $this->assertSame('https://example.com/.well-known/mercure?topic=https%3A%2F%2Ffoo%2Fbar&lastEventID=urn%3Auuid%3A13697bc5-e3c6-48cf-99c8-9d64c26f1a2f&Last-Event-ID=urn%3Auuid%3A13697bc5-e3c6-48cf-99c8-9d64c26f1a2f', $url);
    }

    public function testMercureWithGrantsOption()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/');
        $requestStack->push($request);

        $extension = new MercureExtension($registry, new Authorization($registry), $requestStack);

        $extension->mercure(null, ['grants' => ['https://foo/bar']]);

        $this->assertInstanceOf(Cookie::class, $request->attributes->get('_mercure_authorization_cookies')['']);
    }

    public function testMercureWithGrantShapedArrayOption()
    {
        $tokenFactory = $this->createMock(TokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->equalTo([new Grant([Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], ['https://foo/bar'])]), $this->anything())
        ;

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $tokenFactory
        ));

        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/');
        $requestStack->push($request);

        $extension = new MercureExtension($registry, new Authorization($registry), $requestStack);

        $extension->mercure(null, ['grants' => [['actions' => [Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], 'topics' => ['https://foo/bar']]]]);

        $this->assertInstanceOf(Cookie::class, $request->attributes->get('_mercure_authorization_cookies')['']);
    }

    /**
     * @group legacy
     */
    public function testMercurePublishOptionIsDeprecated()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/');
        $requestStack->push($request);

        $extension = new MercureExtension($registry, new Authorization($registry), $requestStack);

        $extension->mercure(null, ['publish' => ['https://foo/bar']]);

        $this->assertInstanceOf(Cookie::class, $request->attributes->get('_mercure_authorization_cookies')['']);
    }

    public function testMercurePayloadWithoutSubscribeThrows()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://example.com/'));

        $extension = new MercureExtension($registry, new Authorization($registry), $requestStack);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A "payload" option requires a non-null "subscribe" option.');

        $extension->mercure(null, ['payload' => 'foo']);
    }

    public function testMercureV1FlatListIsExact()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class),
            protocolVersion: ProtocolVersion::V1,
        ));

        $extension = new MercureExtension($registry);

        $url = $extension->mercure(['https://foo/bar']);

        $this->assertSame('https://example.com/.well-known/mercure?match=https%3A%2F%2Ffoo%2Fbar', $url);
    }

    public function testMercureV1MatcherTypedTopics()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class),
            protocolVersion: ProtocolVersion::V1,
        ));

        $extension = new MercureExtension($registry);

        $url = $extension->mercure([
            'exact' => ['https://foo/bar'],
            'urlpattern' => ['https://foo/books/:id'],
        ]);

        $this->assertSame('https://example.com/.well-known/mercure?match=https%3A%2F%2Ffoo%2Fbar&match_urlpattern=https%3A%2F%2Ffoo%2Fbooks%2F%3Aid', $url);
    }

    public function testMercureV1MatcherTypeIsUrlEncoded()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class),
            protocolVersion: ProtocolVersion::V1,
        ));

        $extension = new MercureExtension($registry);

        $url = $extension->mercure(['urlpattern&topic=evil' => ['https://foo/bar']]);

        $this->assertSame('https://example.com/.well-known/mercure?match_urlpattern%26topic%3Devil=https%3A%2F%2Ffoo%2Fbar', $url);
    }

    public function testMercureLegacyHubThrowsOnNonExactMatcherType()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class),
        ));

        $extension = new MercureExtension($registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Topic matcher type(s) "urlpattern" require the Mercure protocol 1.0');

        $extension->mercure(['urlpattern' => ['https://foo/books/:id']]);
    }

    public function testMercureLegacyHubAcceptsPureExactMatcherArray()
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class),
        ));

        $extension = new MercureExtension($registry);

        $url = $extension->mercure(['exact' => ['https://foo/bar']]);

        $this->assertSame('https://example.com/.well-known/mercure?topic=https%3A%2F%2Ffoo%2Fbar', $url);
    }
}
