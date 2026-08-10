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
 * Decorates a token factory with a fixed set of additional claims (typically a hub's
 * "iss"/"aud"/"sub"/"client_id", required by RFC 9068 access tokens under the Mercure
 * protocol 1.0), merged into every call to {@see self::create()}. A claim passed at
 * call time overrides the corresponding default.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DefaultClaimsTokenFactory implements TokenFactoryInterface
{
    /**
     * @param mixed[] $defaultClaims
     */
    public function __construct(
        private readonly TokenFactoryInterface $decorated,
        private readonly array $defaultClaims,
    ) {
    }

    public function create(array $grants = [], array $additionalClaims = []): string
    {
        return $this->decorated->create($grants, $additionalClaims + $this->defaultClaims);
    }
}
