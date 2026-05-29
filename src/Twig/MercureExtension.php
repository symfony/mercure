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
     * @param string|Matcher|array<string|Matcher>|null                                                                                                                                            $matchers a matcher (or list of matchers) to subscribe to; pass `null` to get the bare hub URL (useful for publishing in JavaScript)
     * @param array{subscribe?: string|Matcher|array<string|Matcher>, publish?: string|Matcher|array<string|Matcher>, additionalClaims?: array<string, mixed>, lastEventId?: string, hub?: string} $options  options forwarded to the JWT factory
     *
     * @return string the URL of the hub with the appropriate query parameters (if any)
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
            foreach ($matchers as $matcher) {
                [$param, $value] = $this->matcherToQueryParam($matcher);
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

    /**
     * @return array{0: string, 1: string}
     */
    private function matcherToQueryParam(string|Matcher $matcher): array
    {
        if (\is_string($matcher)) {
            trigger_deprecation('symfony/mercure', '0.8', 'Passing a string as a topic is deprecated, use the "%s" class instead.', Matcher::class);

            return ['topic', $matcher];
        }

        return ['match'.($matcher->matchType ?? ''), $matcher->match];
    }
}
