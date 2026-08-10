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
     * Create a token carrying the given grants and/or additional claims.
     *
     * Under the legacy Mercure protocol (0.x), which has no "authorization_details" concept,
     * a factory derives its "mercure.subscribe"/"mercure.publish" claim from each grant's
     * {@see Grant::ACTION_SUBSCRIBE}/{@see Grant::ACTION_PUBLISH} action and rejects a grant
     * carrying a "payload" (see {@see ProtocolVersion::Legacy}).
     *
     * @param Grant[] $grants
     * @param mixed[] $additionalClaims an array of additional claims for the JWT, e.g. the
     *                                  "iss"/"aud"/"sub"/"client_id" claims required by RFC 9068
     *                                  access tokens under the Mercure protocol 1.0
     */
    public function create(array $grants = [], array $additionalClaims = []): string;
}
