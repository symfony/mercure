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

namespace Symfony\Component\Mercure\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mercure\EventSubscriber\SetCookieSubscriber;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class SetCookieSubscriberTest extends TestCase
{
    public function testOnKernelResponse(): void
    {
        $subscriber = new SetCookieSubscriber();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $cookies = ['' => Cookie::create('mercureAuthorization')];
        $request->attributes->set('_mercure_authorization_cookies', $cookies);
        $response = new Response();
        $event = new ResponseEvent($kernel, $request, 1 /*HttpKernelInterface::MAIN_REQUEST*/, $response);

        $subscriber->onKernelResponse($event);

        $this->assertFalse($request->attributes->has('_mercure_authorization_cookies'));
        $this->assertSame(array_values($cookies), $response->headers->getCookies());
    }

    public function testWiring(): void
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, new SetCookieSubscriber());
        $this->assertArrayHasKey(KernelEvents::RESPONSE, SetCookieSubscriber::getSubscribedEvents());
    }
}
