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
 * Builds the Mercure protocol 1.0 claim set (an RFC 9068 access token carrying an
 * RFC 9396 "authorization_details" claim), shared by every TokenFactoryInterface
 * implementation that supports the protocol 1.0 wire format.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class AuthorizationDetailsClaims
{
    private const TYPE = 'https://mercure.rocks/authorization-detail';

    /**
     * @param array<int, string>|array<string, string[]>|null $subscribe
     * @param array<int, string>|array<string, string[]>|null $publish
     * @param mixed[]                                          $additionalClaims
     *
     * @return mixed[]
     */
    public static function build(?array $subscribe, ?array $publish, array $additionalClaims, ?int $jwtLifetime): array
    {
        $payload = $additionalClaims['mercure']['payload'] ?? null;
        unset($additionalClaims['mercure']);

        $entries = [];

        if (null !== $subscribe) {
            $topics = self::toTopicObjects(MatcherInput::normalize($subscribe));
            if ([] !== $topics) {
                $entry = [
                    'type' => self::TYPE,
                    'actions' => ['subscribe'],
                    'topics' => $topics,
                ];
                if (null !== $payload) {
                    $entry['payload'] = $payload;
                }
                $entries[] = $entry;
            } elseif (null !== $payload) {
                throw new InvalidArgumentException('The "payload" additional claim requires at least one subscribe topic.');
            }
        }

        if (null !== $publish) {
            $topics = self::toTopicObjects(MatcherInput::normalize($publish));
            if ([] !== $topics) {
                $entries[] = [
                    'type' => self::TYPE,
                    'actions' => ['publish'],
                    'topics' => $topics,
                ];
            }
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
