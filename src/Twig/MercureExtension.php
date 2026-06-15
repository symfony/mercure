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
use Symfony\Component\Mercure\TopicMatcher;
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
     * @param string|TopicMatcher|array<string|TopicMatcher>|null                                                                                        $topics  A topic or an array of topics to subscribe for. Plain strings use the legacy "topic" parameter, while TopicMatcher instances use the Mercure V1 "match"/"matchURLPattern" parameters. If this parameter is omitted or `null` is passed, the URL of the hub will be returned (useful for publishing in JavaScript).
     * @param array{subscribe?: string[]|string, publish?: string[]|string, additionalClaims?: array<string, mixed>, lastEventId?: string, hub?: string} $options The options to pass to the JWT factory
     *
     * @return string The URL of the hub with the appropriate "topic" query parameters (if any)
     */
    public function mercure(string|TopicMatcher|array|null $topics = null, array $options = []): string
    {
        $hub = $options['hub'] ?? null;
        $url = $this->hubRegistry->getHub($hub)->getPublicUrl();
        if (null !== $topics) {
            // We cannot use http_build_query() because this method doesn't support generating multiple query parameters with the same name without the [] suffix
            $separator = '?';
            foreach (\is_array($topics) ? $topics : [$topics] as $topic) {
                if ($topic instanceof TopicMatcher) {
                    $parameter = 'match'.(TopicMatcher::EXACT === $topic->matchType ? '' : $topic->matchType);
                    $topic = $topic->match;
                } else {
                    $parameter = 'topic';
                }

                $url .= $separator.$parameter.'='.rawurlencode($topic);
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
