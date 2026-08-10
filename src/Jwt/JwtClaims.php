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

namespace Symfony\Component\Mercure\Jwt;

use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\MatcherInput;

/**
 * Shared claim-building logic used by every TokenFactoryInterface implementation:
 * "0 means auto" lifetime resolution, and the Mercure protocol 1.0 claim set (an
 * RFC 9068 access token carrying an RFC 9396 "authorization_details" claim).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class JwtClaims
{
    private const AUTHORIZATION_DETAIL_TYPE = 'https://mercure.rocks/authorization-detail';

    /**
     * "0" auto-picks a default ("session.cookie_lifetime", falling back to 3600 if that's unset/0);
     * any other int is returned as-is; "null" means "no automatic expiration" — it is returned as-is
     * too, but callers must then set "exp" themselves in additionalClaims, or the token never expires.
     */
    public static function resolveLifetime(?int $jwtLifetime): ?int
    {
        return 0 === $jwtLifetime ? ((int) \ini_get('session.cookie_lifetime') ?: 3600) : $jwtLifetime;
    }

    /**
     * Rejects the pre-0.8 create($subscribe, $publish, $additionalClaims) calling convention,
     * which PHP would otherwise accept silently (the extra third argument is ignored, and string
     * "grants" only raise property-access warnings), minting a token with dropped grants, a bogus
     * numeric claim, or a lost "exp".
     *
     * @param mixed[] $grants
     * @param mixed[] $additionalClaims
     */
    public static function assertCreateArguments(array $grants, array $additionalClaims): void
    {
        foreach ($grants as $grant) {
            if (!$grant instanceof Grant) {
                throw new InvalidArgumentException(\sprintf('"$grants" must be a list of "%s" instances, "%s" given. As of symfony/mercure 0.8, TokenFactoryInterface::create() takes a list of grants instead of the former "$subscribe"/"$publish" topic lists.', Grant::class, get_debug_type($grant)));
            }
        }

        foreach ($additionalClaims as $name => $value) {
            if (!\is_string($name)) {
                throw new InvalidArgumentException(\sprintf('"$additionalClaims" must be indexed by claim name, integer key %d given. As of symfony/mercure 0.8, TokenFactoryInterface::create() takes ($grants, $additionalClaims) instead of the former ($subscribe, $publish, $additionalClaims).', $name));
            }
        }
    }

    /**
     * @param Grant[]  $grants
     * @param mixed[]  $additionalClaims
     * @param int|null $jwtLifetime      already resolved via self::resolveLifetime(); "null" skips the automatic
     *                                   "exp" claim entirely (non-expiring token unless the caller sets "exp" in
     *                                   $additionalClaims itself), any other int sets "exp" to now + that many
     *                                   seconds when "exp" isn't already present
     *
     * @return mixed[]
     */
    public static function buildAuthorizationDetails(array $grants, array $additionalClaims, ?int $jwtLifetime): array
    {
        $entries = [];

        foreach ($grants as $grant) {
            if ([] === $grant->actions) {
                throw new InvalidArgumentException('A grant must declare at least one action.');
            }

            $topics = self::toTopicObjects(MatcherInput::normalize($grant->topics));
            if ([] === $topics) {
                if (null !== $grant->payload) {
                    throw new InvalidArgumentException('A grant "payload" requires at least one topic.');
                }
                continue;
            }

            if (null !== $grant->payload && !\in_array(Grant::ACTION_SUBSCRIBE, $grant->actions, true)) {
                throw new InvalidArgumentException('A grant "payload" is only meaningful when its "actions" include "subscribe"; the hub never surfaces one on a publish-only grant.');
            }

            $entry = [
                'type' => self::AUTHORIZATION_DETAIL_TYPE,
                'actions' => $grant->actions,
                'topics' => $topics,
            ];
            if (null !== $grant->payload) {
                $entry['payload'] = $grant->payload;
            }
            $entries[] = $entry;
        }

        if ([] !== $entries) {
            $additionalClaims['authorization_details'] = $entries;
        }

        foreach (['iss', 'aud', 'sub', 'client_id'] as $required) {
            $value = $additionalClaims[$required] ?? null;
            if (null === $value || '' === $value || [] === $value) {
                throw new InvalidArgumentException(\sprintf('The "%s" additional claim is required by RFC 9068 access tokens.', $required));
            }
        }

        $additionalClaims['iat'] ??= new \DateTimeImmutable();
        $additionalClaims['jti'] ??= bin2hex(random_bytes(16));
        // A "null" $jwtLifetime intentionally means "non-expiring token": skip the automatic "exp"
        // claim rather than defaulting it, unlike "0" (see resolveLifetime()). A resource server may
        // reject an RFC 9068 access token that never carries one, so callers relying on "null" here
        // must set "exp" themselves in $additionalClaims.
        if (null !== $jwtLifetime && !\array_key_exists('exp', $additionalClaims)) {
            $additionalClaims['exp'] = new \DateTimeImmutable("+{$jwtLifetime} seconds");
        }

        return $additionalClaims;
    }

    /**
     * @param array<string, string[]> $normalized
     *
     * @return array<int, array{match: string, match_type?: string}>
     */
    private static function toTopicObjects(array $normalized): array
    {
        $topics = [];
        foreach ($normalized as $matcherType => $patterns) {
            foreach ($patterns as $pattern) {
                $topics[] = 'exact' === $matcherType ? ['match' => $pattern] : ['match' => $pattern, 'match_type' => $matcherType];
            }
        }

        return $topics;
    }
}
