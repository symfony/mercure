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
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Matcher;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Twig\MercureExtension;
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class MercureExtensionTest extends TestCase
{
    public function testMercureWithMatcher(): void
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

        $url = $extension->mercure(
            [new Matcher('https://foo/bar')],
            ['subscribe' => [new Matcher('https://foo/:id', 'URLPattern')]],
        );

        $this->assertSame('https://example.com/.well-known/mercure?match=https%3A%2F%2Ffoo%2Fbar', $url);
        $this->assertInstanceOf(Cookie::class, $request->attributes->get('_mercure_authorization_cookies')['']);
    }

    public function testMercureWithMultipleMatcherTypes(): void
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $extension = new MercureExtension($registry);

        $url = $extension->mercure([
            new Matcher('https://foo/bar'),
            new Matcher('https://example.com/books/:id', 'URLPattern'),
            new Matcher('^chat-room-[0-9]+$', 'Regexp'),
        ]);

        $this->assertSame(
            'https://example.com/.well-known/mercure?match=https%3A%2F%2Ffoo%2Fbar&matchURLPattern=https%3A%2F%2Fexample.com%2Fbooks%2F%3Aid&matchRegexp=%5Echat-room-%5B0-9%5D%2B%24',
            $url,
        );
    }

    public function testMercureWithCustomMatcherType(): void
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class)
        ));

        $extension = new MercureExtension($registry);

        $url = $extension->mercure([new Matcher('topic == "/books/1"', 'CEL')]);

        $this->assertSame('https://example.com/.well-known/mercure?matchCEL=topic%20%3D%3D%20%22%2Fbooks%2F1%22', $url);
    }

    public function testMercureLegacyStringEmitsTopicQueryParam(): void
    {
        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            static function (Update $u): string { return 'dummy'; },
            $this->createMock(TokenFactoryInterface::class),
        ));

        $extension = new MercureExtension($registry);

        $url = @$extension->mercure(['https://example.com/books/{id}']);

        $this->assertSame('https://example.com/.well-known/mercure?topic=https%3A%2F%2Fexample.com%2Fbooks%2F%7Bid%7D', $url);
    }

    public function testMercureLastEventId(): void
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

        $url = $extension->mercure([new Matcher('https://foo/bar')], [
            'lastEventId' => 'urn:uuid:13697bc5-e3c6-48cf-99c8-9d64c26f1a2f',
        ]);

        $this->assertSame('https://example.com/.well-known/mercure?match=https%3A%2F%2Ffoo%2Fbar&lastEventID=urn%3Auuid%3A13697bc5-e3c6-48cf-99c8-9d64c26f1a2f&Last-Event-ID=urn%3Auuid%3A13697bc5-e3c6-48cf-99c8-9d64c26f1a2f', $url);
    }
}
