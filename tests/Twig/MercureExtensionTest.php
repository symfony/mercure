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
use Symfony\Component\Mercure\MockHub;
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
            function (Update $u): string { return 'dummy'; },
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
            function (Update $u): string {
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
}
