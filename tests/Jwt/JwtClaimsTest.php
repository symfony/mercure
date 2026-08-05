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

namespace Symfony\Component\Mercure\Tests\Jwt;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Jwt\JwtClaims;

final class JwtClaimsTest extends TestCase
{
    public function testResolveLifetimeNullMeansNoAutoExpiration(): void
    {
        $this->assertNull(JwtClaims::resolveLifetime(null));
    }

    public function testResolveLifetimeExplicitValueIsReturnedAsIs(): void
    {
        $this->assertSame(3600, JwtClaims::resolveLifetime(3600));
    }

    public function testResolveLifetimeZeroFallsBackToSessionCookieLifetimeOrDefault(): void
    {
        $this->assertSame((int) \ini_get('session.cookie_lifetime') ?: 3600, JwtClaims::resolveLifetime(0));
    }
}
