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
use Symfony\Component\Mercure\Internal\MatcherNormalizer;
use Symfony\Component\Mercure\Matcher;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Registers the Twig helper function.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class MercureExtension extends AbstractExtension
{
    public function __construct(
        private readonly HubRegistry $hubRegistry,
        private readonly ?Authorization $authorization = null,
        private readonly ?RequestStack $requestStack = null,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('mercure', $this->mercure(...))];
    }

    /**
     * @param string|Matcher|array<string|Matcher|array<string, mixed>>|null                                                                                                                                                                 $matchers A matcher or list of matchers to subscribe with. Bare strings are interpreted using the hub's protocol version (`topic` on v0, `match`/exact on v1). Pass `null` to get the bare hub URL (useful for publishing in JavaScript).
     * @param array{subscribe?: string|Matcher|array<string|Matcher|array<string, mixed>>, publish?: string|Matcher|array<string|Matcher|array<string, mixed>>, additionalClaims?: array<string, mixed>, lastEventId?: string, hub?: string} $options  Options forwarded to the JWT factory
     *
     * @return string The URL of the hub with the appropriate query parameters (if any)
     */
    public function mercure(string|array|Matcher|null $matchers = null, array $options = []): string
    {
        $hub = $options['hub'] ?? null;
        $hubInstance = $this->hubRegistry->getHub($hub);
        $url = $hubInstance->getPublicUrl();
        if (null !== $matchers) {
            $matchers = \is_array($matchers) ? $matchers : [$matchers];
            // We cannot use http_build_query() because this method doesn't support generating multiple query parameters with the same name without the [] suffix
            $separator = '?';
            foreach (MatcherNormalizer::forQuery($matchers, $hubInstance->getVersion()) as [$param, $value]) {
                $url .= $separator.$param.'='.rawurlencode($value);
                if ('?' === $separator) {
                    $separator = '&';
                }
            }
        }

        if ('' !== ($options['lastEventId'] ?? '')) {
            $encodedLastEventId = rawurlencode($options['lastEventId']);
            // Last-Event-ID is kept for compatibility with older versions of the protocol: https://mercure.rocks/docs/UPGRADE#0-14
            $url .= "&lastEventID=$encodedLastEventId&Last-Event-ID=$encodedLastEventId";
        }

        if (
            null === $this->authorization
            || null === $this->requestStack
            || (!isset($options['subscribe']) && !isset($options['publish']) && !isset($options['additionalClaims']))
            /* @phpstan-ignore-next-line */
            || null === $request = method_exists($this->requestStack, 'getMainRequest') ? $this->requestStack->getMainRequest() : $this->requestStack->getMasterRequest()
        ) {
            return $url;
        }

        $this->authorization->setCookie($request, $options['subscribe'] ?? [], $options['publish'] ?? [], $options['additionalClaims'] ?? [], $hub);

        return $url;
    }
}
