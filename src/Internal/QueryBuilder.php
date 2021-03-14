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

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @internal
 */
final class QueryBuilder
{
    public static function build(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            if (null === $value) {
                continue;
            }

            if (\is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = self::encode($key, $v);
                }

                continue;
            }

            $parts[] = self::encode($key, $value);
        }

        return implode('&', $parts);
    }

    private static function encode($key, $value): string
    {
        // All Mercure's keys are safe, so don't need to be encoded, but it's not a generic solution
        return sprintf('%s=%s', $key, urlencode((string) $value));
    }
}
