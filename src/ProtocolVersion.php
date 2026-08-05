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
 * The version of the Mercure protocol spoken by a hub.
 *
 * @see https://github.com/dunglas/mercure/blob/main/docs/UPGRADE.md
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
enum ProtocolVersion: string
{
    /** The pre-1.0, "legacy" protocol: "topic" query parameter, bare "mercure" JWT claim, "mercureAuthorization" cookie. */
    case Legacy = '0.x';

    /** The Mercure 1.0 protocol: "match"/"match_<type>" query parameters, "authorization_details" JWT claim. */
    case V1 = '1.0';

    public function getDefaultCookieName(): string
    {
        return match ($this) {
            self::Legacy => 'mercureAuthorization',
            self::V1 => '__Secure-mercure_access_token',
        };
    }
}
