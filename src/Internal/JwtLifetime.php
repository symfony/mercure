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

namespace Symfony\Component\Mercure\Internal;

/**
 * Shared "0 means auto" lifetime resolution used by every TokenFactoryInterface implementation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class JwtLifetime
{
    public static function resolve(?int $jwtLifetime): ?int
    {
        return 0 === $jwtLifetime ? ((int) \ini_get('session.cookie_lifetime') ?: 3600) : $jwtLifetime;
    }
}
