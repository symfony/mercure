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

final class Matcher
{
    public function __construct(
        public string $value,
        public MatcherType $type = MatcherType::Exact,
        public mixed $payload = null,
    ) {
    }

    public static function topic(string $value): self
    {
        return new self($value, MatcherType::Topic);
    }

    public static function exact(string $value, mixed $payload = null): self
    {
        return new self($value, MatcherType::Exact, $payload);
    }

    public static function urlPattern(string $value, mixed $payload = null): self
    {
        return new self($value, MatcherType::URLPattern, $payload);
    }

    public static function regexp(string $value, mixed $payload = null): self
    {
        return new self($value, MatcherType::Regexp, $payload);
    }

    public static function fromAny(mixed $input, MercureVersion $version): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (\is_string($input)) {
            return new self($input, $version->defaultMatcherType());
        }

        if (\is_array($input)) {
            if (isset($input['match']) && \is_string($input['match'])) {
                $type = isset($input['matchType']) && \is_string($input['matchType'])
                    ? (MatcherType::tryFrom($input['matchType']) ?? throw new InvalidArgumentException(\sprintf('Unknown matchType "%s".', $input['matchType'])))
                    : MatcherType::Exact;

                return new self($input['match'], $type, $input['payload'] ?? null);
            }

            if (1 === \count($input)) {
                $key = (string) array_key_first($input);
                $value = $input[$key];

                if (\is_string($value)) {
                    foreach (MatcherType::cases() as $type) {
                        if ($type->queryKey() === $key) {
                            return new self($value, $type);
                        }
                    }
                }
            }
        }

        throw new InvalidArgumentException('Invalid matcher input: must be a string, a Matcher instance or a valid configuration array.');
    }
}
