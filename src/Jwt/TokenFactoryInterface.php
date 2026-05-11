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

use Symfony\Component\Mercure\Matcher;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
interface TokenFactoryInterface
{
    /**
     * Create a token that allows publishing to $publish and subscribing to $subscribe.
     *
     * @param array<string|Matcher|array{match: string, matchType?: string, payload?: mixed}>|null $subscribe        matchers that the token will allow subscribing to
     * @param array<string|Matcher|array{match: string, matchType?: string, payload?: mixed}>|null $publish          matchers that the token will allow publishing to
     * @param mixed[]                                                                              $additionalClaims additional claims for the JWT
     */
    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string;
}
