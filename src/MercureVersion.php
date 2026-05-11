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

enum MercureVersion
{
    case V0;
    case V1;

    public function defaultMatcherType(): MatcherType
    {
        return match ($this) {
            self::V0 => MatcherType::Topic,
            self::V1 => MatcherType::Exact,
        };
    }
}
