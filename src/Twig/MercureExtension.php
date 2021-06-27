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

namespace Symfony\Component\Mercure\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Registers the Twig helper function.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class MercureExtension extends AbstractExtension
{
    private HubRegistry $hubRegistry;
    private ?Authorization $authorization;
    private ?RequestStack $requestStack;

    public function __construct(HubRegistry $hubRegistry, ?Authorization $authorization = null, ?RequestStack $requestStack = null)
    {
        $this->hubRegistry = $hubRegistry;
        $this->authorization = $authorization;
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('mercure', [$this, 'mercure'])];
    }

    /**
     * @param string|string[]|null $topics A topic or an array of topics to subscribe for. If this parameter is omitted or `null` is passed, the URL of the hub will be returned (useful for publishing in JavaScript).
     */
    public function mercure($topics = null, array $options = []): string
    {
        $hub = $options['hub'] ?? null;
        $url = $this->hubRegistry->getHub($hub)->getPublicUrl();
        if (null !== $topics) {
            // We cannot use http_build_query() because this method doesn't support generating multiple query parameters with the same name without the [] suffix
            $separator = '?';
            foreach ((array) $topics as $topic) {
                $url .= $separator.'topic='.rawurlencode($topic);
                if ('?' === $separator) {
                    $separator = '&';
                }
            }
        }

        if (
            null === $this->authorization ||
            null === $this->requestStack ||
            (!isset($options['subscribe']) && !isset($options['publish']) && !isset($options['additionalClaims'])) ||
            null === $request = $this->requestStack->getMainRequest()
        ) {
            return $url;
        }

        $this->authorization->setCookie($request, $options['subscribe'] ?? [], $options['publish'] ?? [], $options['additionalClaims'] ?? [], $hub);

        return $url;
    }
}
