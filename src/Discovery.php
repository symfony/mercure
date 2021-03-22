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

namespace Symfony\Component\Mercure;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Discovery service is a helper to add `Link` header to the response.
 */
final class Discovery
{
    private $registry;

    public function __construct(HubRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Add mercure link header to the given request.
     */
    public function addLink(Request $request, ?string $hub = null): void
    {
        // Prevent issues with NelmioCorsBundle
        if ($this->isPreflightRequest($request)) {
            return;
        }

        $hubInstance = $this->registry->getHub($hub);
        $link = new Link('mercure', $hubInstance->getPublicUrl());
        if (null === $linkProvider = $request->attributes->get('_links')) {
            $request->attributes->set('_links', new GenericLinkProvider([$link]));

            return;
        }

        $request->attributes->set('_links', $linkProvider->withLink($link));
    }

    private function isPreflightRequest(Request $request): bool
    {
        return $request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method');
    }
}
