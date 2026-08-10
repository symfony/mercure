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

use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Jwt\Grant;

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
     * @throws InvalidArgumentException if a non-"exact" matcher type is present
     *
     * @return string[]
     */
    public static function flattenToExactOrFail(?array $topics): array
    {
        if (null === $topics || [] === $topics || array_is_list($topics)) {
            return $topics ?? [];
        }

        $unsupported = array_diff(array_keys($topics), ['exact']);
        if ([] !== $unsupported) {
            throw new InvalidArgumentException(\sprintf('Topic matcher type(s) "%s" require the Mercure protocol 1.0 (see Symfony\Component\Mercure\ProtocolVersion::V1); this factory only supports "exact" topic matching.', implode('", "', $unsupported)));
        }

        return $topics['exact'] ?? [];
    }

    /**
     * Normalizes anything a $grants-shaped parameter accepts: a Grant[] list, a bare topic string, a flat topic
     * list, a matcher-type map (one implicit "subscribe" grant each), or a list of Grant-shaped associative arrays
     * (mirroring Grant's constructor — actions/topics/payload — for contexts that can't construct a Grant object
     * directly, e.g. a Twig template).
     *
     * @param Grant[]|array<int, string|array{actions?: string[], topics?: mixed, payload?: mixed}>|array<string, string[]>|string|null $grants
     *
     * @return Grant[]
     */
    public static function normalizeGrants(array|string|null $grants): array
    {
        if (null === $grants || [] === $grants) {
            return [];
        }
        if (\is_string($grants)) {
            return [new Grant([Grant::ACTION_SUBSCRIBE], [$grants])];
        }
        if (!array_is_list($grants)) {
            // a matcher-type map (e.g. ["urlpattern" => [...]]): the topics of one implicit "subscribe" grant
            return [new Grant([Grant::ACTION_SUBSCRIBE], $grants)];
        }

        $first = reset($grants);
        if ($first instanceof Grant) {
            return $grants;
        }
        if (\is_string($first)) {
            return [new Grant([Grant::ACTION_SUBSCRIBE], $grants)];
        }

        return array_map(
            static fn (array $item) => new Grant($item['actions'] ?? [Grant::ACTION_SUBSCRIBE], $item['topics'] ?? [], $item['payload'] ?? null),
            $grants
        );
    }
}
