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
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\MatcherInput;
use Symfony\Component\Mercure\ProtocolVersion;
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
     * @param string|string[]|array<string, string[]>|null                                                                                                                                                                                                                                    $topics  A topic, an array of topics to subscribe for (matched as "exact"), or (Mercure protocol 1.0 hubs only) an associative array mapping a matcher type name ("exact", "urlpattern", or a registered extension type) to a list of patterns of that type. If this parameter is omitted or `null` is passed, the URL of the hub will be returned (useful for publishing in JavaScript).
     * @param array{grants?: Grant[]|string|array<int, string>|array<string, string[]>, subscribe?: string[]|string|array<string, string[]>, publish?: string[]|string|array<string, string[]>, additionalClaims?: array<string, mixed>, payload?: mixed, lastEventId?: string, hub?: string} $options The options to pass to the JWT factory
     *
     * @return string The URL of the hub with the appropriate matcher query parameters (if any)
     */
    public function mercure(string|array|null $topics = null, array $options = []): string
    {
        $hub = $options['hub'] ?? null;
        $hubInstance = $this->hubRegistry->getHub($hub);
        $url = $hubInstance->getPublicUrl();
        if (null !== $topics) {
            // We cannot use http_build_query() because this method doesn't support generating multiple query parameters with the same name without the [] suffix
            $separator = '?';
            if (ProtocolVersion::V1 === $hubInstance->getProtocolVersion()) {
                $normalized = MatcherInput::normalize(\is_string($topics) ? [$topics] : $topics);
                foreach ($normalized as $matcherType => $patterns) {
                    $paramName = 'exact' === $matcherType ? 'match' : 'match_'.rawurlencode($matcherType);
                    foreach ($patterns as $pattern) {
                        $url .= $separator.$paramName.'='.rawurlencode($pattern);
                        $separator = '&';
                    }
                }
            } else {
                foreach (MatcherInput::flattenToExactOrFail(\is_string($topics) ? [$topics] : $topics) as $topic) {
                    $url .= $separator.'topic='.rawurlencode($topic);
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
            || (!isset($options['grants']) && !isset($options['subscribe']) && !isset($options['publish']) && !isset($options['additionalClaims']) && !isset($options['payload']))
            /* @phpstan-ignore-next-line */
            || null === $request = method_exists($this->requestStack, 'getMainRequest') ? $this->requestStack->getMainRequest() : $this->requestStack->getMasterRequest()
        ) {
            return $url;
        }

        $grants = [];
        if (isset($options['subscribe'])) {
            $grants[] = new Grant([Grant::ACTION_SUBSCRIBE], (array) $options['subscribe'], $options['payload'] ?? null);
        } elseif (isset($options['payload'])) {
            throw new InvalidArgumentException('A "payload" option requires a non-null "subscribe" option.');
        }
        if (isset($options['grants'])) {
            $extra = $options['grants'];
            if (\is_string($extra)) {
                $extra = [new Grant([Grant::ACTION_SUBSCRIBE], [$extra])];
            } elseif ([] !== $extra && !(reset($extra) instanceof Grant)) {
                $extra = [new Grant([Grant::ACTION_SUBSCRIBE], $extra)];
            }
            $grants = array_merge($grants, $extra);
        }

        $this->authorization->setCookie($request, $grants, $options['publish'] ?? null, $options['additionalClaims'] ?? [], $hub);

        return $url;
    }
}
