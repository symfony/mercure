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
     * @param array<int, string>|array<string, string[]>|null $subscribe        a flat list of topics (matched as
     *                                                                          "exact"), or an associative array
     *                                                                          mapping a matcher type name (e.g.
     *                                                                          "exact", "urlpattern", or a
     *                                                                          registered extension type) to a list
     *                                                                          of patterns of that type. Matcher
     *                                                                          types other than "exact" are only
     *                                                                          meaningful for factories that
     *                                                                          support the Mercure protocol 1.0
     *                                                                          (see {@see ProtocolVersion::V1}).
     * @param array<int, string>|array<string, string[]>|null $publish          same shape as $subscribe
     * @param mixed[]                                         $additionalClaims an array of additional claims for the JWT
     */
    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string;
}
