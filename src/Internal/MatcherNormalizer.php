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

namespace Symfony\Component\Mercure\Internal;

use Symfony\Component\Mercure\Matcher;
use Symfony\Component\Mercure\MatcherType;
use Symfony\Component\Mercure\MercureVersion;

final class MatcherNormalizer
{
    public static function forJwt(array $matchers, MercureVersion $version): array
    {
        $normalizedMatchers = [];
        foreach ($matchers as $entry) {
            if (\is_array($entry) && (isset($entry['match']) || \array_key_exists('match', $entry))) {
                $normalizedMatchers[] = $entry;
                continue;
            }

            $matcher = Matcher::fromAny($entry, $version);
            $normalizedMatchers[] = self::matcherToJwtClaim($matcher, $version);
        }

        return $normalizedMatchers;
    }

    /**
     * @param array $matchers
     * @param MercureVersion $version
     * @return list<array{0: string, 1: string}>
     */
    public static function forQuery(array $matchers, MercureVersion $version): array
    {
        $normalizedMatchers = [];
        foreach ($matchers as $entry) {
            $matcher = Matcher::fromAny($entry, $version);
            $type = MercureVersion::V0 === $version ? MatcherType::Topic : $matcher->type;
            $normalizedMatchers[] = [$type->queryKey(), $matcher->value];
        }

        return $normalizedMatchers;
    }

    /**
     * @return string|array<string, mixed>
     */
    private static function matcherToJwtClaim(Matcher $matcher, MercureVersion $version): string|array
    {
        if (MercureVersion::V0 === $version) {
            return $matcher->value;
        }

        $claim = ['match' => $matcher->value];
        if (MatcherType::Exact !== $matcher->type && MatcherType::Topic !== $matcher->type) {
            $claim['matchType'] = $matcher->type->value;
        }

        if (null !== $matcher->payload) {
            $claim['payload'] = $matcher->payload;
        }

        return $claim;
    }
}
