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
final class FactoryTokenProvider implements TokenProviderInterface
{
    /**
     * @param Grant[] $grants
     * @param mixed[] $additionalClaims an array of additional claims for the JWT, e.g. the "iss"/"aud"/"sub"/"client_id" claims required by RFC 9068 access tokens under the Mercure protocol 1.0
     */
    public function __construct(
        private readonly TokenFactoryInterface $factory,
        private readonly array $grants = [],
        private readonly array $additionalClaims = [],
    ) {
    }

    public function getJwt(): string
    {
        return $this->factory->create($this->grants, $this->additionalClaims);
    }
}
