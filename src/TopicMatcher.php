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

/**
 * A topic matcher for Mercure V1.
 * @author Léa BAR <contact@0xlea.fr>
 */
final class TopicMatcher
{
    public const EXACT = 'Exact';
    public const URL_PATTERN = 'URLPattern';

    /**
     * @param string                        $match     the topic matcher
     * @param self::EXACT|self::URL_PATTERN $matchType the matcher type
     */
    public function __construct(
        public readonly string $match,
        public readonly string $matchType = self::EXACT,
    ) {
        if (self::EXACT !== $matchType && self::URL_PATTERN !== $matchType) {
            throw new InvalidArgumentException(\sprintf('Unsupported match type "%s", expected one of "%s", "%s".', $matchType, self::EXACT, self::URL_PATTERN));
        }
    }
}
