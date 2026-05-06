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
     * @param string|array<string|array<string, string>>|null                                                                                            $matchers A matcher value or list of matchers to subscribe with. Strings produce `match=<value>` (exact). Single-key arrays let you pick another matcher type, e.g. `['matchURLPattern' => 'https://example.com/books/:id']` or `['matchRegexp' => '^chat-room-[0-9]+$']`. Pass `null` to get the bare hub URL (useful for publishing in JavaScript).
     * @param array{subscribe?: string|array<string|array<string, mixed>>, publish?: string|array<string|array<string, mixed>>, additionalClaims?: array<string, mixed>, lastEventId?: string, hub?: string} $options  Options forwarded to the JWT factory
     *
     * @return string The URL of the hub with the appropriate "match*" query parameters (if any)
     */
    public function mercure(string|array|null $matchers = null, array $options = []): string
    {
        $hub = $options['hub'] ?? null;
        $url = $this->hubRegistry->getHub($hub)->getPublicUrl();
        if (null !== $matchers) {
            // We cannot use http_build_query() because this method doesn't support generating multiple query parameters with the same name without the [] suffix
            $separator = '?';
            foreach ((array) $matchers as $matcher) {
                if (\is_string($matcher)) {
                    $param = 'match';
                    $value = $matcher;
                } elseif (\is_array($matcher) && 1 === \count($matcher)) {
                    $param = (string) array_key_first($matcher);
                    $value = (string) reset($matcher);
                } else {
                    throw new \InvalidArgumentException('Each matcher must be a string or a single-key array like ["matchURLPattern" => "https://example.com/books/:id"].');
                }

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
