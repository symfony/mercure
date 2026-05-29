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

/**
* @author Léa BAR <contact@0xlea.Fr>
 */
final class Matcher implements \JsonSerializable
{
    /**
     * @param string      $match     the value to match against (URI, regular expression etc...)
     * @param string|null $matchType the type of matcher (URLPattern, Regexp etc...)
     * @param array|null  $payload   the optional payload
     */
    public function __construct(
        public string $match,
        public ?string $matchType = null,
        public ?array $payload = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = ['match' => $this->match];
        if (null !== $this->matchType) {
            $data['matchType'] = $this->matchType;
        }
        if (null !== $this->payload) {
            $data['payload'] = $this->payload;
        }

        return $data;
    }
}
