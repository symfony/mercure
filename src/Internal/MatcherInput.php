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

use Symfony\Component\Mercure\Exception\InvalidArgumentException;

/**
 * Normalizes the two shapes accepted for a list of subscribe/publish topics:
 * a flat list of exact topics, or an associative array mapping a matcher
 * type name (e.g. "exact", "urlpattern", or any registered extension type)
 * to the list of patterns for that type.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 *
 * @internal
 */
final class MatcherInput
{
    /**
     * @param string[]|array<string, string[]>|null $topics
     *
     * @return array<string, string[]>
     */
    public static function normalize(?array $topics): array
    {
        if (null === $topics || [] === $topics) {
            return [];
        }

        return array_is_list($topics) ? ['exact' => $topics] : $topics;
    }

    /**
     * Flattens a matcher-typed shape down to a flat exact-topic list, for factories/protocols
     * that only understand "exact" topic matching.
     *
     * @param string[]|array<string, string[]>|null $topics
     *
     * @return string[]
     *
     * @throws InvalidArgumentException if a non-"exact" matcher type is present
     */
    public static function flattenToExactOrFail(?array $topics): array
    {
        if (null === $topics || [] === $topics || array_is_list($topics)) {
            return $topics ?? [];
        }

        $unsupported = array_diff(array_keys($topics), ['exact']);
        if ([] !== $unsupported) {
            throw new InvalidArgumentException(\sprintf(
                'Topic matcher type(s) "%s" require the Mercure protocol 1.0 (see Symfony\Component\Mercure\ProtocolVersion::V1); this factory only supports "exact" topic matching.',
                implode('", "', $unsupported)
            ));
        }

        return $topics['exact'] ?? [];
    }
}
