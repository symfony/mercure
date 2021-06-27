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

namespace Symfony\Component\Mercure\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the cookies created by the Authorization helper class.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class SetCookieSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event)
    {
        if (
            !$event->isMainRequest() ||
            null === $cookies = $event->getRequest()->attributes->get('_mercure_authorization_cookies')) {
            return;
        }

        $response = $event->getResponse();
        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }
}
