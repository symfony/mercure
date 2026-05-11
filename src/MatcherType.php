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

enum MatcherType: string
{
    case Topic = 'topic';
    case Exact = 'exact';
    case URLPattern = 'URLPattern';
    case Regexp = 'Regexp';

    public function queryKey(): string
    {
        return match ($this) {
            self::Topic => 'topic',
            self::Exact => 'match',
            self::URLPattern => 'matchURLPattern',
            self::Regexp => 'matchRegexp',
        };
    }
}
