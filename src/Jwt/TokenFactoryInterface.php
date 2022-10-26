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
     * @param string[]|null $subscribe        a list of topics that the token will allow subscribing to
     * @param string[]|null $publish          a list of topics that the token will allow publishing to
     * @param mixed[]       $additionalClaims an array of additional claims for the JWT
     */
    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string;
}
